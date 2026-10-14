<?php

use App\Http\Middleware\PerbaruiStatusSesiBerkala;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pengguna yang sudah login dan membuka /login dikirim ke beranda
        // sesuai perannya (bukan selalu /dashboard, yang ditolak untuk Trainer).
        $middleware->redirectUsersTo(fn () => route(auth()->user()->homeRouteName()));

        // Status sesi mengikuti tanggal dan jam tanpa menunggu cron.
        $middleware->web(append: [PerbaruiStatusSesiBerkala::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
