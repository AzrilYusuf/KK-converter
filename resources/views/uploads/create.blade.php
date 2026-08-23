@extends('layouts.app')

@section('title', 'Upload Kartu Keluarga')

@section('content')
<div class="max-w-xl mx-auto px-4 py-16">
    <h1 class="text-2xl font-semibold text-gray-900 mb-2">Upload Kartu Keluarga</h1>
    <p class="text-sm text-gray-600 mb-8">
        Unggah satu berkas hasil scan Kartu Keluarga (PDF, JPG, atau PNG). Anda akan mengetik ulang datanya secara manual pada langkah berikutnya.
    </p>

    @if ($errors->any())
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Berkas Kartu Keluarga</label>
            <input
                type="file"
                name="file"
                id="file"
                accept=".pdf,.jpg,.jpeg,.png"
                required
                class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-gray-800 file:text-white file:text-sm file:cursor-pointer cursor-pointer"
            >
            <p class="mt-1 text-xs text-gray-500">Format: PDF, JPG, atau PNG. Maksimal 10MB.</p>
        </div>

        <button
            type="submit"
            class="inline-flex items-center justify-center w-full rounded-md bg-gray-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-900 transition"
        >
            Unggah &amp; Lanjutkan
        </button>
    </form>
</div>
@endsection
