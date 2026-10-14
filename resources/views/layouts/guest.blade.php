<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Masuk · Sistem Penjadwalan Pelatihan HRGZ</title>
        <meta name="description" content="Masuk ke sistem penjadwalan dan monitoring pelatihan HR GenZ Academy.">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-mist font-sans text-ink">
        <div class="flex min-h-screen">
            {{-- Panel merek: hanya tampil di layar md ke atas (mengikuti desain login) --}}
            <div class="hidden flex-col justify-between bg-navy p-10 text-white md:flex md:w-[46%]">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-md bg-cobalt font-bold">HG</span>
                    <span class="text-xl font-bold">HRGZ</span>
                </div>
                <p class="max-w-xs text-2xl font-bold leading-snug">Training Operations &amp; Scheduling</p>
            </div>

            <div class="flex flex-1 items-center justify-center bg-white p-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
