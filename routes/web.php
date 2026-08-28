<?php

use App\Http\Controllers\KeluargaController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UploadController::class, 'create'])->name('upload.create');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

Route::get('/kk', [KeluargaController::class, 'index'])->name('keluarga.index');
Route::get('/kk/{upload:session_id}', [KeluargaController::class, 'create'])->name('keluarga.form');
Route::post('/kk/{upload:session_id}', [KeluargaController::class, 'store'])->name('keluarga.store');
Route::post('/kk/{upload:session_id}/ocr', [KeluargaController::class, 'ocr'])->name('keluarga.ocr');
Route::get('/kk/{upload:session_id}/success', [KeluargaController::class, 'success'])->name('keluarga.success');
Route::get('/kk/{upload:session_id}/export', [KeluargaController::class, 'export'])->name('keluarga.export');

Route::get('/kk/{keluarga}/edit', [KeluargaController::class, 'edit'])->name('keluarga.edit');
Route::put('/kk/{keluarga}', [KeluargaController::class, 'update'])->name('keluarga.update');
Route::delete('/kk/{keluarga}', [KeluargaController::class, 'destroy'])->name('keluarga.destroy');
Route::get('/kk/{keluarga}/download', [KeluargaController::class, 'download'])->name('keluarga.download');
