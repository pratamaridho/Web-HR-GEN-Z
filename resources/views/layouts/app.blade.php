@php
    $pengguna = auth()->user();
    $labelPeran = $pengguna->trainer?->nama ?? $pengguna->peran()?->labelPanjang() ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · Penjadwalan Pelatihan HRGZ' : 'Penjadwalan Pelatihan HRGZ' }}</title>
        <meta name="description" content="Sistem penjadwalan dan monitoring pelatihan HR GenZ Academy: jadwal sesi, trainer, kehadiran, dan dashboard.">
        <meta name="robots" content="noindex">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-mist font-sans text-ink">
        <div x-data="{ buka: false }">
            {{-- Topbar mobile --}}
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-white px-4 md:hidden">
                <button type="button" @click="buka = true" aria-label="Buka menu"
                        class="grid h-10 w-10 place-items-center rounded-md border border-line text-navy">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="font-bold">HRGZ</span>
                <span class="ml-auto text-xs text-mute">{{ $labelPeran }}</span>
            </header>

            {{-- Scrim mobile --}}
            <div x-show="buka" x-cloak @click="buka = false" class="fixed inset-0 z-30 bg-ink/50 md:hidden"></div>

            @include('layouts.sidebar')

            {{-- Konten --}}
            <div class="min-h-screen md:pl-60">
                <main class="mx-auto w-full max-w-[1200px] px-4 py-6 md:px-8 md:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
