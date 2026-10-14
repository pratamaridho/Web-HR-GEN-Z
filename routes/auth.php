<?php

use App\Modules\Akun\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

// Hanya login dan logout (FR 01). Tidak ada pendaftaran mandiri, reset
// password, verifikasi email, atau profil: akun dibuat lewat seeder dan
// peserta tidak punya akun (PRD bagian 3).
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
