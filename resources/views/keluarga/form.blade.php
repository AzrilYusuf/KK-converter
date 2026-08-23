@extends('layouts.app')

@section('title', 'Isi Data Kartu Keluarga')

@section('content')
<div class="flex flex-col lg:flex-row lg:h-[calc(100vh-57px)]">
    {{-- Left: document preview --}}
    <div class="lg:w-1/2 bg-gray-800 lg:h-full flex flex-col">
        <div class="px-4 py-2 text-xs text-gray-300 border-b border-gray-700">
            Referensi: {{ $upload->original_filename }}
        </div>
        <div class="flex-1 overflow-auto p-4">
            @if ($upload->file_type === 'pdf')
                <iframe src="{{ $previewUrl }}" class="w-full h-[80vh] lg:h-full bg-white rounded"></iframe>
            @else
                <img src="{{ $previewUrl }}" alt="Preview Kartu Keluarga" class="w-full h-auto rounded mx-auto">
            @endif
        </div>
    </div>

    {{-- Right: tabbed form --}}
    <div class="lg:w-1/2 lg:h-full lg:overflow-y-auto bg-white">
        <div
            x-data="kkForm(@js(old('anggota', [])))"
            class="p-6"
        >
            <h1 class="text-xl font-semibold text-gray-900 mb-1">Input Data Kartu Keluarga</h1>
            <p class="text-sm text-gray-500 mb-6">Isi data sesuai dokumen di sebelah kiri.</p>

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <p class="font-medium mb-1">Terdapat kesalahan pada isian:</p>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex gap-6 text-sm font-medium">
                    <button type="button" @click="activeTab = 'kepala'"
                        :class="activeTab === 'kepala' ? 'border-gray-800 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 transition">
                        1. Kepala Keluarga &amp; Alamat
                    </button>
                    <button type="button" @click="activeTab = 'anggota'"
                        :class="activeTab === 'anggota' ? 'border-gray-800 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 transition">
                        2. Anggota Keluarga
                    </button>
                    <button type="button" @click="activeTab = 'dinas'"
                        :class="activeTab === 'dinas' ? 'border-gray-800 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 transition">
                        3. Kepala Dinas
                    </button>
                </nav>
            </div>

            <form action="{{ route('keluarga.store', $upload) }}" method="POST">
                @csrf

                {{-- Tab 1: Kepala Keluarga & Alamat --}}
                <div x-show="activeTab === 'kepala'" x-cloak class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. KK</label>
                            <input type="text" name="no_kk" value="{{ old('no_kk') }}" maxlength="16"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kepala Keluarga</label>
                            <input type="text" name="nama_kepala_keluarga" value="{{ old('nama_kepala_keluarga') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <textarea name="alamat" rows="2"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">{{ old('alamat') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RT</label>
                            <input type="text" name="rt" value="{{ old('rt') }}" maxlength="3"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RW</label>
                            <input type="text" name="rw" value="{{ old('rw') }}" maxlength="3"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                            <input type="text" name="kode_pos" value="{{ old('kode_pos') }}" maxlength="10"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Desa/Kelurahan</label>
                            <input type="text" name="desa_kelurahan" value="{{ old('desa_kelurahan') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota</label>
                            <input type="text" name="kabupaten_kota" value="{{ old('kabupaten_kota') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                            <input type="text" name="provinsi" value="{{ old('provinsi') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Anggota Keluarga</label>
                            <input type="number" name="jumlah_anggota_keluarga" min="1" value="{{ old('jumlah_anggota_keluarga') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Dikeluarkan</label>
                            <input type="date" name="tanggal_dikeluarkan" value="{{ old('tanggal_dikeluarkan') }}"
                                class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Anggota Keluarga --}}
                <div x-show="activeTab === 'anggota'" x-cloak class="space-y-4">
                    <template x-for="(item, index) in anggota" :key="index">
                        <div class="border border-gray-200 rounded-md p-4 space-y-3 relative">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800" x-text="'Anggota ' + (index + 1)"></h3>
                                <button type="button" @click="removeAnggota(index)"
                                    x-show="anggota.length > 1"
                                    class="text-xs text-red-600 hover:text-red-800">
                                    Hapus
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                                    <input type="text" x-model="item.nama_lengkap" :name="`anggota[${index}][nama_lengkap]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">NIK</label>
                                    <input type="text" x-model="item.nik" :name="`anggota[${index}][nik]`" maxlength="16"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Kelamin</label>
                                    <select x-model="item.jenis_kelamin" :name="`anggota[${index}][jenis_kelamin]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tempat Lahir</label>
                                    <input type="text" x-model="item.tempat_lahir" :name="`anggota[${index}][tempat_lahir]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Lahir</label>
                                    <input type="date" x-model="item.tanggal_lahir" :name="`anggota[${index}][tanggal_lahir]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Agama</label>
                                    <input type="text" x-model="item.agama" :name="`anggota[${index}][agama]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Pendidikan</label>
                                    <input type="text" x-model="item.pendidikan" :name="`anggota[${index}][pendidikan]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Pekerjaan</label>
                                    <input type="text" x-model="item.jenis_pekerjaan" :name="`anggota[${index}][jenis_pekerjaan]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Golongan Darah</label>
                                    <input type="text" x-model="item.golongan_darah" :name="`anggota[${index}][golongan_darah]`" maxlength="10"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Status Perkawinan</label>
                                    <select x-model="item.status_perkawinan" :name="`anggota[${index}][status_perkawinan]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                        <option value="Belum Kawin">Belum Kawin</option>
                                        <option value="Kawin">Kawin</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Perkawinan</label>
                                    <input type="date" x-model="item.tanggal_perkawinan" :name="`anggota[${index}][tanggal_perkawinan]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Status Hubungan Dalam Keluarga</label>
                                    <input type="text" x-model="item.status_hubungan_dalam_keluarga" :name="`anggota[${index}][status_hubungan_dalam_keluarga]`" placeholder="Kepala Keluarga / Istri / Anak / dst."
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Kewarganegaraan</label>
                                    <input type="text" x-model="item.kewarganegaraan" :name="`anggota[${index}][kewarganegaraan]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">No. Paspor</label>
                                    <input type="text" x-model="item.no_paspor" :name="`anggota[${index}][no_paspor]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">No. KITAP</label>
                                    <input type="text" x-model="item.no_kitap" :name="`anggota[${index}][no_kitap]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Ayah</label>
                                    <input type="text" x-model="item.nama_ayah" :name="`anggota[${index}][nama_ayah]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Ibu</label>
                                    <input type="text" x-model="item.nama_ibu" :name="`anggota[${index}][nama_ibu]`"
                                        class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="addAnggota()"
                        class="w-full rounded-md border border-dashed border-gray-300 py-2 text-sm text-gray-600 hover:border-gray-400 hover:text-gray-800 transition">
                        + Tambah Anggota
                    </button>
                </div>

                {{-- Tab 3: Kepala Dinas --}}
                <div x-show="activeTab === 'dinas'" x-cloak class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kepala Dinas</label>
                        <input type="text" name="nama_kepala_dinas" value="{{ old('nama_kepala_dinas') }}"
                            class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP Kepala Dinas</label>
                        <input type="text" name="nip_kepala_dinas" value="{{ old('nip_kepala_dinas') }}"
                            class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button type="submit"
                        class="w-full rounded-md bg-gray-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-900 transition">
                        Simpan Data Kartu Keluarga
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function kkForm(initialAnggota) {
        const emptyAnggota = () => ({
            nama_lengkap: '',
            nik: '',
            jenis_kelamin: 'Laki-laki',
            tempat_lahir: '',
            tanggal_lahir: '',
            agama: '',
            pendidikan: '',
            jenis_pekerjaan: '',
            golongan_darah: '',
            status_perkawinan: 'Belum Kawin',
            tanggal_perkawinan: '',
            status_hubungan_dalam_keluarga: '',
            kewarganegaraan: 'WNI',
            no_paspor: '',
            no_kitap: '',
            nama_ayah: '',
            nama_ibu: '',
        });

        return {
            activeTab: 'kepala',
            anggota: (initialAnggota && initialAnggota.length > 0)
                ? initialAnggota.map(a => ({ ...emptyAnggota(), ...a }))
                : [emptyAnggota()],
            addAnggota() {
                this.anggota.push(emptyAnggota());
            },
            removeAnggota(index) {
                this.anggota.splice(index, 1);
            },
        };
    }
</script>
@endsection
