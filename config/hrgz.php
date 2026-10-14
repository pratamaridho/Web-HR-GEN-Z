<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Room sesi online
    |--------------------------------------------------------------------------
    |
    | Daftar room yang dapat dipilih pada form sesi. Kapasitas room bukan
    | aturan sistem (PRD OP 10); hanya bentrok waktu yang dicek.
    |
    */

    'rooms' => ['Zoom Room A', 'Zoom Room B', 'Zoom Room C', 'Kelas Lab 1', 'Kelas Lab 2', 'Kelas Lab 3'],

    /*
    |--------------------------------------------------------------------------
    | Status sesi otomatis
    |--------------------------------------------------------------------------
    |
    | Sesi menjadi Ongoing saat jam mulai dan Completed setelah jam selesai.
    | Saat jam mulai tiba, sesi dengan peserta di bawah kuota minimum menjadi
    | Cancelled. Kuota 0 mematikan pembatalan otomatis. Kuota bukan aturan
    | brief; nilainya usulan dan dapat diubah di sini.
    |
    */

    'status_otomatis' => env('HRGZ_STATUS_OTOMATIS', true),

    'kuota_minimum' => 5,

    /*
    |--------------------------------------------------------------------------
    | Nama lengkap skema
    |--------------------------------------------------------------------------
    |
    | Database hanya menyimpan kode skema. Nama lengkap dipakai untuk label
    | batch, misalnya "Batch 1 · Junior Web Programmer 2026 · Online".
    |
    */

    'skema' => [
        'BP' => 'Basic Programming',
        'JNA' => 'Junior Network Administrator',
        'JWP' => 'Junior Web Programmer',
        'NA' => 'Network Administrator',
        'TMJK' => 'Teknisi Muda Jaringan Komputer',
        'TUJK' => 'Teknisi Utama Jaringan Komputer',
    ],

];
