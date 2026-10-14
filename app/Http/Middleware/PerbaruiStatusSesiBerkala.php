<?php

namespace App\Http\Middleware;

use App\Modules\Penjadwalan\Actions\PerbaruiStatusSesi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memperbarui status sesi saat pengguna yang login membuka aplikasi, paling
 * sering sekali per 30 detik. Tanpa ini, status baru berubah bila perintah
 * terjadwal (sesi:perbarui-status) dijalankan.
 */
class PerbaruiStatusSesiBerkala
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('hrgz.status_otomatis') && $request->user() && Cache::add('hrgz.status-sesi', true, 30)) {
            app(PerbaruiStatusSesi::class)->execute();
        }

        return $next($request);
    }
}
