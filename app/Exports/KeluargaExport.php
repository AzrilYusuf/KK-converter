<?php

namespace App\Exports;

use App\Models\Keluarga;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class KeluargaExport extends StringValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithTitle
{
    private const MEMBER_HEADINGS = [
        'No', 'Nama Lengkap', 'NIK', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
        'Agama', 'Pendidikan', 'Jenis Pekerjaan', 'Golongan Darah', 'Status Perkawinan',
        'Tanggal Perkawinan', 'Status Hubungan Dalam Keluarga', 'Kewarganegaraan',
        'No. Paspor', 'No. KITAP', 'Nama Ayah', 'Nama Ibu',
    ];

    private int $headerRowCount;

    private int $memberHeadingRow;

    public function __construct(private readonly Keluarga $keluarga)
    {
    }

    public function array(): array
    {
        $rows = [
            ['KARTU KELUARGA'],
            ['No. KK', $this->keluarga->no_kk],
            ['Nama Kepala Keluarga', $this->keluarga->nama_kepala_keluarga],
            ['Alamat', $this->keluarga->alamat],
            ['RT/RW', $this->keluarga->rt.'/'.$this->keluarga->rw],
            ['Kode Pos', $this->keluarga->kode_pos],
            ['Desa/Kelurahan', $this->keluarga->desa_kelurahan],
            ['Kecamatan', $this->keluarga->kecamatan],
            ['Kabupaten/Kota', $this->keluarga->kabupaten_kota],
            ['Provinsi', $this->keluarga->provinsi],
            ['Jumlah Anggota Keluarga', $this->keluarga->jumlah_anggota_keluarga],
            ['Tanggal Dikeluarkan', optional($this->keluarga->tanggal_dikeluarkan)->format('d-m-Y')],
            ['Nama Kepala Dinas', $this->keluarga->nama_kepala_dinas],
            ['NIP Kepala Dinas', $this->keluarga->nip_kepala_dinas],
            [],
        ];

        $this->headerRowCount = count($rows);

        $rows[] = self::MEMBER_HEADINGS;
        $this->memberHeadingRow = count($rows);

        foreach ($this->keluarga->anggotaKeluarga as $index => $anggota) {
            $rows[] = [
                $index + 1,
                $anggota->nama_lengkap,
                $anggota->nik,
                $anggota->jenis_kelamin,
                $anggota->tempat_lahir,
                optional($anggota->tanggal_lahir)->format('d-m-Y'),
                $anggota->agama,
                $anggota->pendidikan,
                $anggota->jenis_pekerjaan,
                $anggota->golongan_darah,
                $anggota->status_perkawinan,
                optional($anggota->tanggal_perkawinan)->format('d-m-Y'),
                $anggota->status_hubungan_dalam_keluarga,
                $anggota->kewarganegaraan,
                $anggota->no_paspor,
                $anggota->no_kitap,
                $anggota->nama_ayah,
                $anggota->nama_ibu,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'KK '.$this->keluarga->no_kk;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastColumn = $sheet->getHighestColumn();

                $sheet->mergeCells('A1:'.$lastColumn.'1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $sheet->getStyle('A2:A'.$this->headerRowCount)->getFont()->setBold(true);

                $sheet->getStyle('A'.$this->memberHeadingRow.':'.$lastColumn.$this->memberHeadingRow)
                    ->getFont()->setBold(true);
                $sheet->getStyle('A'.$this->memberHeadingRow.':'.$lastColumn.$this->memberHeadingRow)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9D9D9');
            },
        ];
    }
}
