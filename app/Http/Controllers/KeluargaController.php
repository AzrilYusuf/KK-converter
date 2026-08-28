<?php

namespace App\Http\Controllers;

use App\Exports\KeluargaExport;
use App\Http\Requests\StoreKeluargaRequest;
use App\Http\Requests\UpdateKeluargaRequest;
use App\Models\Keluarga;
use App\Models\Upload;
use App\Services\KkOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class KeluargaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $keluargaList = Keluarga::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('no_kk', 'like', "%{$search}%")
                        ->orWhere('nama_kepala_keluarga', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('keluarga.index', [
            'keluargaList' => $keluargaList,
            'search' => $search,
        ]);
    }

    public function create(Upload $upload)
    {
        if ($upload->status === 'completed') {
            return redirect()->route('keluarga.success', $upload);
        }

        if ($upload->status === 'uploaded') {
            $upload->update(['status' => 'in_progress']);
        }

        return view('keluarga.form', [
            'upload' => $upload,
            'previewUrl' => $this->previewUrl($upload),
        ]);
    }

    public function store(StoreKeluargaRequest $request, Upload $upload)
    {
        $data = $request->validated();
        $anggotaList = $data['anggota'];
        unset($data['anggota']);

        DB::transaction(function () use ($data, $anggotaList, $upload) {
            $keluarga = $upload->keluarga()->create($data);

            foreach ($anggotaList as $anggota) {
                $keluarga->anggotaKeluarga()->create($anggota);
            }

            $upload->update(['status' => 'completed']);
        });

        return redirect()->route('keluarga.success', $upload);
    }

    public function ocr(Upload $upload, KkOcrService $ocrService)
    {
        try {
            $result = $ocrService->extract($upload);
        } catch (Throwable $e) {
            Log::error('KK OCR endpoint failed', ['upload_id' => $upload->id, 'message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'OCR gagal diproses. Silakan isi data secara manual.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'fields' => $result['fields'],
            'anggota' => $result['anggota'],
        ]);
    }

    public function success(Upload $upload)
    {
        $keluarga = $upload->keluarga()->with('anggotaKeluarga')->first();

        abort_if(! $keluarga, 404);

        return view('keluarga.success', [
            'upload' => $upload,
            'keluarga' => $keluarga,
        ]);
    }

    public function export(Upload $upload)
    {
        $keluarga = $upload->keluarga()->with('anggotaKeluarga')->first();

        abort_if(! $keluarga, 404);

        $filename = 'exports/KK_'.$keluarga->no_kk.'_'.time().'.xlsx';

        try {
            Excel::store(new KeluargaExport($keluarga), $filename, 'local');

            $fullPath = Storage::disk('local')->path($filename);

            return response()->download($fullPath, 'KK_'.$keluarga->no_kk.'.xlsx')
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            Log::error('KK export failed', ['upload_id' => $upload->id, 'message' => $e->getMessage()]);

            if (Storage::disk('local')->exists($filename)) {
                Storage::disk('local')->delete($filename);
            }

            return redirect()->route('keluarga.success', $upload)
                ->with('error', 'Gagal membuat berkas XLSX. Silakan coba lagi.');
        }
    }

    public function edit(Keluarga $keluarga)
    {
        $keluarga->load(['anggotaKeluarga', 'upload']);

        return view('keluarga.edit', [
            'keluarga' => $keluarga,
            'upload' => $keluarga->upload,
            'previewUrl' => $keluarga->upload ? $this->previewUrl($keluarga->upload) : null,
            'initialAnggota' => old('anggota', $keluarga->anggotaKeluarga->map(fn ($anggota) => [
                'nama_lengkap' => $anggota->nama_lengkap,
                'nik' => $anggota->nik,
                'jenis_kelamin' => $anggota->jenis_kelamin,
                'tempat_lahir' => $anggota->tempat_lahir,
                'tanggal_lahir' => optional($anggota->tanggal_lahir)->format('Y-m-d'),
                'agama' => $anggota->agama,
                'pendidikan' => $anggota->pendidikan,
                'jenis_pekerjaan' => $anggota->jenis_pekerjaan,
                'golongan_darah' => $anggota->golongan_darah,
                'status_perkawinan' => $anggota->status_perkawinan,
                'tanggal_perkawinan' => optional($anggota->tanggal_perkawinan)->format('Y-m-d'),
                'status_hubungan_dalam_keluarga' => $anggota->status_hubungan_dalam_keluarga,
                'kewarganegaraan' => $anggota->kewarganegaraan,
                'no_paspor' => $anggota->no_paspor,
                'no_kitap' => $anggota->no_kitap,
                'nama_ayah' => $anggota->nama_ayah,
                'nama_ibu' => $anggota->nama_ibu,
            ])->toArray()),
        ]);
    }

    public function update(UpdateKeluargaRequest $request, Keluarga $keluarga)
    {
        $data = $request->validated();
        $anggotaList = $data['anggota'];
        unset($data['anggota']);

        DB::transaction(function () use ($data, $anggotaList, $keluarga) {
            $keluarga->update($data);
            $keluarga->anggotaKeluarga()->delete();

            foreach ($anggotaList as $anggota) {
                $keluarga->anggotaKeluarga()->create($anggota);
            }
        });

        return redirect()->route('keluarga.index')
            ->with('success', 'Data Kartu Keluarga berhasil diperbarui.');
    }

    public function destroy(Keluarga $keluarga)
    {
        DB::transaction(function () use ($keluarga) {
            $upload = $keluarga->upload;

            $keluarga->anggotaKeluarga()->delete();
            $keluarga->delete();

            if ($upload) {
                $this->deleteUploadFiles($upload);
                $upload->delete();
            }
        });

        return redirect()->route('keluarga.index')
            ->with('success', 'Data Kartu Keluarga berhasil dihapus.');
    }

    public function download(Keluarga $keluarga)
    {
        $keluarga->load('anggotaKeluarga');

        $filename = 'exports/KK_'.$keluarga->no_kk.'_'.time().'.xlsx';

        try {
            Excel::store(new KeluargaExport($keluarga), $filename, 'local');

            $fullPath = Storage::disk('local')->path($filename);

            return response()->download($fullPath, 'KK_'.$keluarga->no_kk.'.xlsx')
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            Log::error('KK download failed', ['keluarga_id' => $keluarga->id, 'message' => $e->getMessage()]);

            if (Storage::disk('local')->exists($filename)) {
                Storage::disk('local')->delete($filename);
            }

            return redirect()->route('keluarga.index')
                ->with('error', 'Gagal membuat berkas XLSX. Silakan coba lagi.');
        }
    }

    private function previewUrl(Upload $upload): string
    {
        $previewPath = $upload->file_path;

        if ($upload->file_type !== 'pdf') {
            $candidate = 'kk/'.$upload->session_id.'_preview.'.$upload->file_type;

            if (Storage::disk('public')->exists($candidate)) {
                $previewPath = $candidate;
            }
        }

        return Storage::disk('public')->url($previewPath);
    }

    private function deleteUploadFiles(Upload $upload): void
    {
        if (Storage::disk('public')->exists($upload->file_path)) {
            Storage::disk('public')->delete($upload->file_path);
        }

        if ($upload->file_type !== 'pdf') {
            $previewPath = 'kk/'.$upload->session_id.'_preview.'.$upload->file_type;

            if (Storage::disk('public')->exists($previewPath)) {
                Storage::disk('public')->delete($previewPath);
            }
        }
    }
}
