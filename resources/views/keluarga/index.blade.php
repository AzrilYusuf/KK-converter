@extends('layouts.app')

@section('title', 'Daftar Kartu Keluarga')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Daftar Kartu Keluarga</h1>
        <a href="{{ route('upload.create') }}"
            class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 transition">
            + Tambah KK Baru
        </a>
    </div>

    <form method="GET" action="{{ route('keluarga.index') }}" class="mb-4">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. KK atau Nama Kepala Keluarga..."
            class="w-full max-w-md rounded-md border-gray-300 border px-3 py-2 text-sm">
    </form>

    <div class="bg-white rounded-md border border-gray-200 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">No. KK</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Kepala Keluarga</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Alamat</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Jumlah Anggota</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($keluargaList as $keluarga)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $keluarga->no_kk }}</td>
                        <td class="px-4 py-3">{{ $keluarga->nama_kepala_keluarga }}</td>
                        <td class="px-4 py-3 max-w-xs truncate" title="{{ $keluarga->alamat }}">{{ $keluarga->alamat }}</td>
                        <td class="px-4 py-3">{{ $keluarga->jumlah_anggota_keluarga }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('keluarga.download', $keluarga) }}" class="text-gray-600 hover:text-gray-900">Unduh</a>
                                <a href="{{ route('keluarga.edit', $keluarga) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                                <form action="{{ route('keluarga.destroy', $keluarga) }}" method="POST"
                                    onsubmit="return confirm('Hapus data Kartu Keluarga {{ $keluarga->no_kk }}? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            {{ $search ? 'Tidak ada data yang cocok.' : 'Belum ada data Kartu Keluarga.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $keluargaList->links() }}
    </div>
</div>
@endsection
