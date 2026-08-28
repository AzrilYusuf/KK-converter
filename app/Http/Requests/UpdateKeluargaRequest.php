<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKeluargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_kk' => ['required', 'digits:16', Rule::unique('keluarga', 'no_kk')->ignore($this->route('keluarga'))],
            'nama_kepala_keluarga' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'rt' => ['required', 'string', 'max:3'],
            'rw' => ['required', 'string', 'max:3'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'desa_kelurahan' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'kabupaten_kota' => ['required', 'string', 'max:255'],
            'provinsi' => ['required', 'string', 'max:255'],
            'jumlah_anggota_keluarga' => ['required', 'integer', 'min:1'],
            'tanggal_dikeluarkan' => ['required', 'date'],
            'nama_kepala_dinas' => ['required', 'string', 'max:255'],
            'nip_kepala_dinas' => ['required', 'string', 'max:255'],

            'anggota' => ['required', 'array', 'min:1'],
            'anggota.*.nama_lengkap' => ['required', 'string', 'max:255'],
            'anggota.*.nik' => ['required', 'digits:16'],
            'anggota.*.jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'anggota.*.tempat_lahir' => ['required', 'string', 'max:255'],
            'anggota.*.tanggal_lahir' => ['required', 'date'],
            'anggota.*.agama' => ['required', 'string', 'max:255'],
            'anggota.*.pendidikan' => ['required', 'string', 'max:255'],
            'anggota.*.jenis_pekerjaan' => ['required', 'string', 'max:255'],
            'anggota.*.golongan_darah' => ['required', 'string', 'max:10'],
            'anggota.*.status_perkawinan' => ['required', Rule::in(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])],
            'anggota.*.tanggal_perkawinan' => ['nullable', 'date'],
            'anggota.*.status_hubungan_dalam_keluarga' => ['required', 'string', 'max:255'],
            'anggota.*.kewarganegaraan' => ['required', 'string', 'max:255'],
            'anggota.*.no_paspor' => ['nullable', 'string', 'max:255'],
            'anggota.*.no_kitap' => ['nullable', 'string', 'max:255'],
            'anggota.*.nama_ayah' => ['required', 'string', 'max:255'],
            'anggota.*.nama_ibu' => ['required', 'string', 'max:255'],
        ];
    }
}
