@extends('layouts.app')

@section('title', 'Data Tersimpan')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16">
    <div class="text-center mb-8">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900">Data Kartu Keluarga Tersimpan</h1>
        <p class="text-sm text-gray-600 mt-2">
            No. KK {{ $keluarga->no_kk }} — {{ $keluarga->nama_kepala_keluarga }}
        </p>
    </div>

    <div class="bg-white border border-gray-200 rounded-md p-6 mb-6">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
                <dt class="text-gray-500">No. KK</dt>
                <dd class="text-gray-900 font-medium">{{ $keluarga->no_kk }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Kepala Keluarga</dt>
                <dd class="text-gray-900 font-medium">{{ $keluarga->nama_kepala_keluarga }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Jumlah Anggota Tercatat</dt>
                <dd class="text-gray-900 font-medium">{{ $keluarga->anggotaKeluarga->count() }} orang</dd>
            </div>
            <div>
                <dt class="text-gray-500">Alamat</dt>
                <dd class="text-gray-900 font-medium">{{ $keluarga->kecamatan }}, {{ $keluarga->kabupaten_kota }}</dd>
            </div>
        </dl>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('keluarga.export', $upload) }}"
            class="flex-1 inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-900 transition">
            Export ke XLSX
        </a>
        <a href="{{ route('upload.create') }}"
            class="flex-1 inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Unggah KK Lain
        </a>
    </div>
</div>
@endsection
