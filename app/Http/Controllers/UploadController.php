<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadRequest;
use App\Models\Upload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class UploadController extends Controller
{
    public function create()
    {
        return view('uploads.create');
    }

    public function store(StoreUploadRequest $request)
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = $extension === 'jpeg' ? 'jpg' : $extension;
        $sessionId = (string) Str::uuid();
        $filename = $sessionId.'.'.$fileType;

        $path = $file->storeAs('kk', $filename, 'public');

        if (in_array($fileType, ['jpg', 'png'], true)) {
            $this->generatePreview($path, $sessionId, $fileType);
        }

        $upload = Upload::create([
            'session_id' => $sessionId,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $fileType,
            'status' => 'uploaded',
        ]);

        return redirect()->route('keluarga.form', $upload);
    }

    private function generatePreview(string $path, string $sessionId, string $fileType): void
    {
        try {
            $manager = new ImageManager(Driver::class);
            $image = $manager->decodePath(Storage::disk('public')->path($path));
            $image->scaleDown(width: 1600);
            $image->save(Storage::disk('public')->path('kk/'.$sessionId.'_preview.'.$fileType));
        } catch (Throwable $e) {
            Log::warning('KK preview generation failed', ['session_id' => $sessionId, 'message' => $e->getMessage()]);
        }
    }
}
