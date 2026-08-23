<?php

use App\Http\Controllers\KeluargaController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UploadController::class, 'create'])->name('upload.create');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

Route::get('/kk/{upload:session_id}', [KeluargaController::class, 'create'])->name('keluarga.form');
Route::post('/kk/{upload:session_id}', [KeluargaController::class, 'store'])->name('keluarga.store');
Route::post('/kk/{upload:session_id}/ocr', [KeluargaController::class, 'ocr'])->name('keluarga.ocr');
Route::get('/kk/{upload:session_id}/success', [KeluargaController::class, 'success'])->name('keluarga.success');
Route::get('/kk/{upload:session_id}/export', [KeluargaController::class, 'export'])->name('keluarga.export');
