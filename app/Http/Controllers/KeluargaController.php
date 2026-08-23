<?php

namespace App\Http\Controllers;

use App\Exports\KeluargaExport;
use App\Http\Requests\StoreKeluargaRequest;
use App\Models\Upload;
use App\Services\KkOcrService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class KeluargaController extends Controller
{
    public function create(Upload $upload)
    {
        if ($upload->status === 'completed') {
            return redirect()->route('keluarga.success', $upload);
        }

        if ($upload->status === 'uploaded') {
            $upload->update(['status' => 'in_progress']);
        }

        $previewPath = $upload->file_path;

        if ($upload->file_type !== 'pdf') {
            $candidate = 'kk/'.$upload->session_id.'_preview.'.$upload->file_type;

            if (Storage::disk('public')->exists($candidate)) {
                $previewPath = $candidate;
            }
        }

        $previewUrl = Storage::disk('public')->url($previewPath);

        return view('keluarga.form', [
            'upload' => $upload,
            'previewUrl' => $previewUrl,
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
}
