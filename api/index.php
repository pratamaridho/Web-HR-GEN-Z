<?php

/**
 * Titik masuk untuk Vercel (runtime vercel-php). Vercel hanya menyediakan
 * /tmp yang dapat ditulis dan tidak menyimpan berkas antar permintaan, jadi:
 * - folder storage Laravel dipindah ke /tmp,
 * - database SQLite disalin dari database/demo.db ke /tmp saat server menyala,
 * - data yang diubah hilang ketika server berhenti (cukup untuk demo).
 */

$tmp = sys_get_temp_dir();

// Folder yang ditulis Laravel (cache, sesi, tampilan terkompilasi, log).
$storage = $tmp.'/hrgz-storage';
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $folder) {
    if (! is_dir("$storage/$folder")) {
        mkdir("$storage/$folder", 0777, true);
    }
}
putenv("LARAVEL_STORAGE_PATH=$storage");
$_ENV['LARAVEL_STORAGE_PATH'] = $storage;

// Di belakang proxy Vercel, permintaan sampai sebagai HTTP biasa.
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Database SQLite sementara: salinan database/demo.db (sudah berisi data contoh),
// sehingga server tidak perlu menjalankan migrasi saat menyala.
$database = getenv('DB_DATABASE') ?: $tmp.'/hrgz.sqlite';
putenv("DB_DATABASE=$database");
$_ENV['DB_DATABASE'] = $database;

if (! file_exists($database)) {
    $sementara = $database.'.'.getmypid();
    copy(__DIR__.'/../database/demo.db', $sementara);
    rename($sementara, $database);
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
