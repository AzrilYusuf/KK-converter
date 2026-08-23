<?php

namespace App\Services;

use App\Models\Upload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;
use thiagoalessio\TesseractOCR\TesseractOCR;

class KkOcrService
{
    /**
     * Label patterns for simple "Label : Value" fields found in the KK
     * header/footer.
     */
    private const LABELS = [
        'nama_kepala_keluarga' => ['Nama\s+Kepa.a\s+Keluarga'],
        'alamat' => ['Alamat', 'Alarat'],
        'kode_pos' => ['Kode\s*Pos'],
        'desa_kelurahan' => ['Desa\s*/?\s*Kelurahan'],
        'kecamatan' => ['Kecamatan'],
        'kabupaten_kota' => ['Kabupaten\s*/?\s*Kota'],
        'provinsi' => ['Provinsi'],
        'tanggal_dikeluarkan' => ['Dikeluarkan\s+Tanggal', 'Tanggal\s+Dikeluarkan'],
    ];

    /**
     * Anggota keluarga is split across two tables on the printed KK,
     * joined by a shared row number. Columns are located by proportional
     * position within each table's detected width rather than by
     * reading header text, because the header row's own OCR is
     * frequently too garbled to match reliably (it's small print and
     * often wraps across two lines). This is a heuristic calibrated to
     * the standard KK layout — it will drift on scans with unusual
     * cropping, skew, or column proportions.
     */
    private const TABLE1_COLUMNS = [
        'nama_lengkap', 'nik', 'jenis_kelamin', 'tempat_lahir',
        'tanggal_lahir', 'agama', 'pendidikan', 'jenis_pekerjaan', 'golongan_darah',
    ];

    private const TABLE1_WEIGHTS = [0.5, 2.5, 2.0, 1.4, 1.6, 1.3, 1.1, 2.2, 2.4, 1.1];

    private const TABLE2_COLUMNS = [
        'status_perkawinan', 'tanggal_perkawinan', 'status_hubungan_dalam_keluarga',
        'kewarganegaraan', 'no_paspor', 'no_kitap', 'nama_ayah', 'nama_ibu',
    ];

    private const TABLE2_WEIGHTS = [0.5, 1.6, 1.1, 1.8, 1.3, 1.0, 1.0, 2.2, 2.2];

    private const TABLE2_HEADER_KEYWORDS = ['KEWARGANEGARAAN', 'PERKAWINAN', 'PASPOR', 'KITAP'];

    private const MAX_TABLE_ROWS = 10;

    public function extract(Upload $upload): array
    {
        $imagePath = $this->resolveImagePath($upload);

        if (! $imagePath) {
            return ['fields' => [], 'anggota' => []];
        }

        try {
            $languages = $this->resolveLanguages();

            $fields = [];

            try {
                $text = (new TesseractOCR($imagePath))->lang(...$languages)->run();
                $fields = $this->parse($text);
            } catch (Throwable $e) {
                Log::warning('KK OCR (text) failed', ['upload_id' => $upload->id, 'message' => $e->getMessage()]);
            }

            $anggota = [];

            try {
                $tsv = (new TesseractOCR($imagePath))->lang(...$languages)->tsv()->run();
                $anggota = $this->parseAnggotaTables($tsv);
            } catch (Throwable $e) {
                Log::warning('KK OCR (table) failed', ['upload_id' => $upload->id, 'message' => $e->getMessage()]);
            }

            return ['fields' => $fields, 'anggota' => $anggota];
        } finally {
            $this->cleanupTemp($upload, $imagePath);
        }
    }

    private function resolveLanguages(): array
    {
        try {
            $available = (new TesseractOCR)->availableLanguages();
        } catch (Throwable $e) {
            return ['eng'];
        }

        return in_array('ind', $available, true) ? ['ind', 'eng'] : ['eng'];
    }

    private function resolveImagePath(Upload $upload): ?string
    {
        if ($upload->file_type === 'pdf') {
            return $this->rasterizePdf(Storage::disk('public')->path($upload->file_path));
        }

        $previewPath = 'kk/'.$upload->session_id.'_preview.'.$upload->file_type;

        if (Storage::disk('public')->exists($previewPath)) {
            return Storage::disk('public')->path($previewPath);
        }

        return Storage::disk('public')->path($upload->file_path);
    }

    private function rasterizePdf(string $absolutePdfPath): ?string
    {
        $prefix = sys_get_temp_dir().'/kk_ocr_'.Str::random(12);

        $process = new Process(['pdftoppm', '-png', '-r', '200', '-f', '1', '-l', '1', $absolutePdfPath, $prefix]);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('KK PDF rasterization failed', ['message' => $process->getErrorOutput()]);

            return null;
        }

        $matches = glob($prefix.'*.png');

        return $matches[0] ?? null;
    }

    private function cleanupTemp(Upload $upload, ?string $imagePath): void
    {
        if ($upload->file_type === 'pdf' && $imagePath && str_starts_with($imagePath, sys_get_temp_dir())) {
            @unlink($imagePath);
        }
    }

    private function parse(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        $result = [
            ...$this->extractNoKk($lines),
            ...$this->extractRtRw($lines),
            ...$this->extractKepalaDinas($lines),
        ];

        $result += $this->extractLabeledFields($text);

        if (isset($result['tanggal_dikeluarkan'])) {
            $date = $this->normalizeDate($result['tanggal_dikeluarkan']);

            if ($date) {
                $result['tanggal_dikeluarkan'] = $date;
            } else {
                unset($result['tanggal_dikeluarkan']);
            }
        }

        return array_filter($result, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * KK's two-column header/footer sometimes gets OCR'd onto a single
     * line with only a single space between columns, so splitting on
     * whitespace runs isn't reliable. Instead, find every label's
     * position in the raw text and let a value run until the next
     * known label (wherever it is) or the end of the line.
     */
    private function extractLabeledFields(string $text): array
    {
        $occurrences = [];

        foreach (self::LABELS as $key => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match('#'.$pattern.'#ui', $text, $m, PREG_OFFSET_CAPTURE)) {
                    $occurrences[] = [
                        'key' => $key,
                        'start' => $m[0][1],
                        'labelEnd' => $m[0][1] + strlen($m[0][0]),
                    ];

                    break;
                }
            }
        }

        usort($occurrences, fn ($a, $b) => $a['start'] <=> $b['start']);

        $result = [];

        foreach ($occurrences as $i => $occ) {
            if (isset($result[$occ['key']])) {
                continue;
            }

            $nextStart = $occurrences[$i + 1]['start'] ?? null;
            $lineEnd = strpos($text, "\n", $occ['labelEnd']);
            $lineEnd = $lineEnd === false ? strlen($text) : $lineEnd;

            $end = $nextStart !== null ? min($nextStart, $lineEnd) : $lineEnd;
            $raw = substr($text, $occ['labelEnd'], max(0, $end - $occ['labelEnd']));
            $value = trim($raw, " \t:.-)(");

            if ($value !== '') {
                $result[$occ['key']] = $value;
            }
        }

        return $result;
    }

    private function extractNoKk(array $lines): array
    {
        $headerText = implode(' ', array_slice($lines, 0, 8));

        if (preg_match('/No\.?\s*[:.\-]?\s*([\d\s]{14,25})/ui', $headerText, $m)) {
            $digits = preg_replace('/\D/', '', $m[1]);

            if (strlen($digits) === 16) {
                return ['no_kk' => $digits];
            }
        }

        return [];
    }

    private function extractRtRw(array $lines): array
    {
        foreach ($lines as $line) {
            if (preg_match('/RT\s*\/?\s*RW\s*[:.\-]?\s*(\S+)\s*\/\s*(\S+)/ui', $line, $m)) {
                $rt = $m[1] === '-' ? '' : preg_replace('/\D/', '', $m[1]);
                $rw = $m[2] === '-' ? '' : preg_replace('/\D/', '', $m[2]);

                $result = [];
                if ($rt !== '') {
                    $result['rt'] = $rt;
                }
                if ($rw !== '') {
                    $result['rw'] = $rw;
                }

                return $result;
            }
        }

        return [];
    }

    private function extractKepalaDinas(array $lines): array
    {
        foreach ($lines as $i => $line) {
            if (preg_match('/^NIP\.?\s*[:.]?\s*([\d\s]+.*)$/ui', trim($line), $m)) {
                $result = ['nip_kepala_dinas' => trim($m[1])];

                for ($j = $i - 1; $j >= 0; $j--) {
                    $candidate = trim($lines[$j]);

                    if ($candidate === '' || preg_match('/Tanda\s*Tangan|Cap\s*Jempol/ui', $candidate)) {
                        continue;
                    }

                    $result['nama_kepala_dinas'] = $candidate;
                    break;
                }

                return $result;
            }
        }

        return [];
    }

    // -- Anggota table extraction (TSV / word bounding boxes) -------------

    private function parseAnggotaTables(string $tsv): array
    {
        $lines = $this->groupTsvIntoLines($tsv);

        $table1HeaderIndex = $this->findHeaderLineIndex($lines, ['NIK']);

        if ($table1HeaderIndex === null) {
            return [];
        }

        $table2HeaderIndex = $this->findHeaderLineIndex($lines, self::TABLE2_HEADER_KEYWORDS, $table1HeaderIndex + 1);

        // table2's header often wraps across two visually adjacent lines
        // (e.g. "Status Tanggal ... Nama Orang Tua" then "Perkawinan ...
        // KITAP Ayah Ibu" right below it) — only the line containing the
        // matched keyword gets found above, so extend backward through
        // closely-spaced lines to also exclude the header's first half
        // from table1's row range.
        $table1StopIndex = $table2HeaderIndex !== null
            ? $this->extendHeaderBlockStart($lines, $table2HeaderIndex)
            : null;

        $table1Rows = $this->extractTableRows(
            $lines,
            $table1HeaderIndex,
            self::TABLE1_COLUMNS,
            self::TABLE1_WEIGHTS,
            $table1StopIndex
        );

        $table2Rows = $table2HeaderIndex !== null
            ? $this->extractTableRows($lines, $table2HeaderIndex, self::TABLE2_COLUMNS, self::TABLE2_WEIGHTS, null)
            : [];

        $count = max(count($table1Rows), count($table2Rows));
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $row = array_merge($table1Rows[$i] ?? [], $table2Rows[$i] ?? []);

            // unused member slots print as "-" placeholder rows; their
            // nama_lengkap cell reliably OCRs empty even when other
            // cells pick up stray noise, so anchor on it specifically
            // rather than falling back to nik.
            if (mb_strlen(trim($row['nama_lengkap'] ?? '')) >= 3) {
                $result[] = $this->normalizeAnggotaRow($row);
            }
        }

        return $result;
    }

    /**
     * Groups Tesseract TSV word-level rows (level 5) into lines, keyed
     * by (block, paragraph, line) so multi-word cells stay together,
     * ordered top-to-bottom as Tesseract read them.
     */
    private function groupTsvIntoLines(string $tsv): array
    {
        $rows = preg_split('/\r\n|\r|\n/', trim($tsv)) ?: [];
        array_shift($rows);

        $groups = [];
        $order = [];

        foreach ($rows as $row) {
            if ($row === '') {
                continue;
            }

            $cols = explode("\t", $row);

            if (count($cols) < 12) {
                continue;
            }

            [$level, , $block, $par, $lineNum, , $left, $top, $width, , , $text] = $cols;

            if ((int) $level !== 5) {
                continue;
            }

            $text = trim($text);

            if ($text === '') {
                continue;
            }

            $key = $block.':'.$par.':'.$lineNum;

            if (! isset($groups[$key])) {
                $groups[$key] = [];
                $order[] = $key;
            }

            $groups[$key][] = [
                'left' => (int) $left,
                'top' => (int) $top,
                'width' => (int) $width,
                'text' => $text,
            ];
        }

        $lines = [];

        foreach ($order as $key) {
            $words = $groups[$key];
            usort($words, fn ($a, $b) => $a['left'] <=> $b['left']);

            $lines[] = [
                'words' => $words,
                'text' => implode(' ', array_column($words, 'text')),
                'top' => $words[0]['top'],
            ];
        }

        usort($lines, fn ($a, $b) => $a['top'] <=> $b['top']);

        return array_values($lines);
    }

    private function findHeaderLineIndex(array $lines, array $keywords, int $after = 0): ?int
    {
        foreach ($lines as $i => $line) {
            if ($i < $after) {
                continue;
            }

            $upper = mb_strtoupper($line['text']);

            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\b/u', $upper)) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Row 1 of the table is preceded by a numbering/legend line that
     * OCRs unpredictably, so this looks a few lines ahead for the first
     * line that actually starts with "1" (allowing common 1/l/I mix-ups).
     */
    private function findDataStartIndex(array $lines, int $headerIndex, int $lookahead = 6): int
    {
        $limit = min(count($lines) - 1, $headerIndex + $lookahead);

        for ($i = $headerIndex + 1; $i <= $limit; $i++) {
            $firstWord = $lines[$i]['words'][0]['text'] ?? '';

            if (preg_match('/^[1lI][.\)\]]?$/u', $firstWord)) {
                return $i;
            }
        }

        // The row-number token before each member is OCR'd too
        // inconsistently to detect directly (misread as other digits,
        // glued onto the name, or missing). Instead, skip the column
        // numbering legend ("(1) (2) (3) ...") that always immediately
        // follows the header — it reliably OCRs as a run of short,
        // unrelated tokens with no word long enough to be real data.
        $next = $lines[$headerIndex + 1] ?? null;

        if ($next && $this->looksLikeLegendRow($next)) {
            return min($headerIndex + 2, count($lines) - 1);
        }

        return min($headerIndex + 1, count($lines) - 1);
    }

    private function looksLikeLegendRow(array $line): bool
    {
        $words = $line['words'];

        if (count($words) < 4) {
            return false;
        }

        foreach ($words as $word) {
            if (mb_strlen($word['text']) > 4) {
                return false;
            }
        }

        return true;
    }

    private function extendHeaderBlockStart(array $lines, int $headerIndex, int $maxGap = 20): int
    {
        $start = $headerIndex;

        while ($start > 0 && ($lines[$start]['top'] - $lines[$start - 1]['top']) <= $maxGap) {
            $start--;
        }

        return $start;
    }

    private function computeColumnBoundaries(int $left, int $right, array $weights): array
    {
        $total = array_sum($weights);
        $width = $right - $left;
        $boundaries = [$left];
        $cursor = $left;

        foreach ($weights as $weight) {
            $cursor += (int) round(($weight / $total) * $width);
            $boundaries[] = $cursor;
        }

        return $boundaries;
    }

    private function columnIndexForPosition(float $x, array $boundaries): int
    {
        for ($i = 0; $i < count($boundaries) - 1; $i++) {
            if ($x >= $boundaries[$i] && $x < $boundaries[$i + 1]) {
                return $i;
            }
        }

        return count($boundaries) - 2;
    }

    private function extractTableRows(
        array $lines,
        int $headerIndex,
        array $columns,
        array $weights,
        ?int $stopIndex
    ): array {
        $header = $lines[$headerIndex];
        $tableLeft = $header['words'][0]['left'];
        $lastWord = end($header['words']);
        $tableRight = $lastWord['left'] + $lastWord['width'];

        if ($tableRight <= $tableLeft) {
            return [];
        }

        $boundaries = $this->computeColumnBoundaries($tableLeft, $tableRight, $weights);
        $startIndex = $this->findDataStartIndex($lines, $headerIndex);
        $rows = [];

        for ($i = $startIndex; $i < count($lines) && count($rows) < self::MAX_TABLE_ROWS; $i++) {
            if ($stopIndex !== null && $i >= $stopIndex) {
                break;
            }

            $line = $lines[$i];
            $upper = mb_strtoupper($line['text']);

            if (str_contains($upper, 'DIKELUARKAN') || str_contains($upper, 'LEMBAR')) {
                break;
            }

            $cells = array_fill(0, count($columns), []);

            foreach ($line['words'] as $word) {
                $center = $word['left'] + ($word['width'] / 2);
                $colIndex = $this->columnIndexForPosition($center, $boundaries);
                $dataIndex = $colIndex - 1;

                if ($dataIndex >= 0 && $dataIndex < count($columns)) {
                    $cells[$dataIndex][] = $word['text'];
                }
            }

            $row = [];

            foreach ($columns as $idx => $field) {
                $value = trim(implode(' ', $cells[$idx] ?? []));

                if ($value !== '' && $value !== '-') {
                    $row[$field] = $value;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeAnggotaRow(array $row): array
    {
        if (isset($row['nama_lengkap'])) {
            // strip a row-number glued onto the first cell (e.g. "3JOKO" -> "JOKO")
            $row['nama_lengkap'] = preg_replace('/^\d{1,2}(?=[A-Z])/', '', $row['nama_lengkap']);
        }

        if (isset($row['nik'])) {
            $digits = preg_replace('/\D/', '', $row['nik']);

            if (strlen($digits) === 16) {
                $row['nik'] = $digits;
            }
        }

        foreach (['tanggal_lahir', 'tanggal_perkawinan'] as $dateField) {
            if (isset($row[$dateField])) {
                $date = $this->normalizeDate($row[$dateField]);

                if ($date) {
                    $row[$dateField] = $date;
                } else {
                    unset($row[$dateField]);
                }
            }
        }

        if (isset($row['jenis_kelamin'])) {
            $row['jenis_kelamin'] = $this->normalizeEnum($row['jenis_kelamin'], ['Laki-laki', 'Perempuan'])
                ?? $row['jenis_kelamin'];
        }

        if (isset($row['status_perkawinan'])) {
            $row['status_perkawinan'] = $this->normalizeEnum(
                $row['status_perkawinan'],
                ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']
            ) ?? $row['status_perkawinan'];
        }

        return $row;
    }

    private function normalizeDate(string $raw): ?string
    {
        $raw = trim($raw);

        foreach (['d-m-Y', 'd/m/Y', 'd.m.Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $raw);

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function normalizeEnum(string $raw, array $candidates): ?string
    {
        $rawUpper = mb_strtoupper($raw);
        $best = null;
        $bestDistance = null;

        foreach ($candidates as $candidate) {
            $candidateUpper = mb_strtoupper($candidate);

            if (str_contains($rawUpper, $candidateUpper) || str_contains($candidateUpper, $rawUpper)) {
                return $candidate;
            }

            $distance = levenshtein($rawUpper, $candidateUpper);

            if ($bestDistance === null || $distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        if ($best !== null && $bestDistance !== null && $bestDistance <= max(3, (int) (mb_strlen($rawUpper) * 0.4))) {
            return $best;
        }

        return null;
    }
}
