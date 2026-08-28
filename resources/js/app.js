import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.kkForm = function kkForm(initialAnggota, ocrUrl) {
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
        ocrLoading: false,
        ocrMessage: '',
        ocrError: false,
        async runOcr() {
            if (!ocrUrl) {
                this.ocrError = true;
                this.ocrMessage = 'OCR tidak tersedia untuk dokumen ini.';
                return;
            }

            this.ocrLoading = true;
            this.ocrMessage = '';
            this.ocrError = false;

            try {
                const response = await fetch(ocrUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.ocrError = true;
                    this.ocrMessage = data.message || 'OCR gagal diproses. Silakan isi data secara manual.';
                    return;
                }

                const fields = data.fields || {};
                let filledCount = 0;

                for (const [name, value] of Object.entries(fields)) {
                    const input = document.querySelector(`[name="${name}"]`);
                    if (input && value) {
                        input.value = value;
                        filledCount++;
                    }
                }

                const detectedAnggota = data.anggota || [];
                let anggotaFilled = 0;

                if (detectedAnggota.length > 0) {
                    const isUntouched = this.anggota.length === 1
                        && JSON.stringify(this.anggota[0]) === JSON.stringify(emptyAnggota());

                    const proceed = isUntouched || confirm(
                        `Ditemukan ${detectedAnggota.length} anggota dari hasil OCR. ` +
                        'Ini akan menggantikan data anggota yang sudah diisi pada tab Anggota Keluarga. Lanjutkan?'
                    );

                    if (proceed) {
                        this.anggota = detectedAnggota.map(row => ({ ...emptyAnggota(), ...row }));
                        anggotaFilled = detectedAnggota.length;
                    }
                }

                this.ocrError = false;
                this.ocrMessage = (filledCount > 0 || anggotaFilled > 0)
                    ? `${filledCount} field kepala keluarga/dinas terisi, ${anggotaFilled} anggota terdeteksi. `
                        + 'OCR pada tabel anggota rawan keliru (NIK, tanggal, dsb) — periksa setiap baris dengan teliti sebelum menyimpan.'
                    : 'Tidak ada field yang berhasil dikenali. Silakan isi manual.';
            } catch (e) {
                this.ocrError = true;
                this.ocrMessage = 'OCR gagal diproses. Silakan isi data secara manual.';
            } finally {
                this.ocrLoading = false;
            }
        },
    };
};

Alpine.start();
