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
     * header/footer. The anggota keluarga table is intentionally not
     * parsed here — OCR on a multi-column table is unreliable, so that
     * tab stays fully manual.
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

    public function extract(Upload $upload): array
    {
        $imagePath = $this->resolveImagePath($upload);

        if (! $imagePath) {
            return [];
        }

        try {
            $text = (new TesseractOCR($imagePath))
                ->lang(...$this->resolveLanguages())
                ->run();
        } catch (Throwable $e) {
            Log::warning('KK OCR failed', ['upload_id' => $upload->id, 'message' => $e->getMessage()]);

            return [];
        } finally {
            $this->cleanupTemp($upload, $imagePath);
        }

        return $this->parse($text);
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
}
