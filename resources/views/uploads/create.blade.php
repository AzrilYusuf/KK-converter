@extends('layouts.app')

@section('title', 'Upload Kartu Keluarga')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="relative rounded-3xl border-2 border-dashed border-brand-200 bg-white px-6 py-16 text-center">
        <div class="mx-auto mb-6 flex flex-col items-center gap-3">
            <x-brand-icon class="h-6 w-6" />
            <h1 class="text-2xl font-bold text-gray-900">Konversi Kartu Keluarga</h1>
        </div>
        <p class="mx-auto mb-10 max-w-md text-sm text-gray-600">
            Unggah hasil scan Kartu Keluarga (PDF, JPG, atau PNG), lalu ubah menjadi data terstruktur yang siap diunduh sebagai XLSX.
        </p>

        @if ($errors->any())
            <div class="mx-auto mb-6 max-w-md rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm text-left">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data"
            x-data="{ fileName: null }" class="mx-auto max-w-md">
            @csrf

            <label for="file"
                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-brand-500 px-8 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-brand-600">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span x-text="fileName || 'Choose file'" class="truncate"></span>
            </label>
            <input type="file" name="file" id="file" accept=".pdf,.jpg,.jpeg,.png" required
                class="sr-only" @change="fileName = $event.target.files[0]?.name">

            <p class="mt-3 text-xs text-gray-500">Format: PDF, JPG, atau PNG. Maksimal 10MB.</p>

            <button type="submit"
                class="mt-6 w-full rounded-full border border-brand-300 px-8 py-3 text-sm font-semibold text-brand-600 transition hover:bg-brand-50">
                Unggah &amp; Lanjutkan
            </button>
        </form>

        <div class="mt-8 flex items-center justify-center gap-3">
            <button type="button" disabled title="Segera hadir"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-400 cursor-not-allowed">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 16H3z" />
                </svg>
            </button>
            <button type="button" disabled title="Segera hadir"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-400 cursor-not-allowed">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="4" width="16" height="16" rx="2" />
                </svg>
            </button>
            <button type="button" disabled title="Segera hadir"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-400 cursor-not-allowed">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.07 0l1.41-1.41a5 5 0 00-7.07-7.07L10 6" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 00-7.07 0L5.52 12.4a5 5 0 007.07 7.07L14 18" />
                </svg>
            </button>
        </div>
    </div>

    <div class="mt-16 text-center">
        <h2 class="text-xl font-bold text-gray-900 mb-8">Cara Konversi Kartu Keluarga</h2>
        <div class="grid gap-8 sm:grid-cols-3">
            <div>
                <div class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">1</div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Unggah Dokumen</h3>
                <p class="text-xs text-gray-500">Unggah hasil scan KK dalam format PDF, JPG, atau PNG.</p>
            </div>
            <div>
                <div class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">2</div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Isi &amp; Periksa Data</h3>
                <p class="text-xs text-gray-500">OCR membantu mengisi otomatis, Anda tinggal memeriksa dan melengkapi.</p>
            </div>
            <div>
                <div class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">3</div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Unduh XLSX</h3>
                <p class="text-xs text-gray-500">Data yang sudah tersimpan bisa langsung diunduh sebagai berkas XLSX.</p>
            </div>
        </div>
    </div>
</div>
@endsection
