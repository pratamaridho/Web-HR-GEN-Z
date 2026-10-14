@php
    $pengguna = auth()->user();
    $peran = $pengguna?->peran();
    $beranda = $pengguna ? route($pengguna->homeRouteName()) : route('login');
@endphp

@auth
    <x-app-layout title="Akses ditolak">
        <div class="mx-auto mt-10 max-w-md rounded-md border border-line bg-white p-8 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-badbg text-bad">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </div>
            <h1 class="mt-4 text-lg font-bold">Akses ditolak</h1>
            <p class="mt-1 text-sm text-mute">
                @if ($peran)
                    Peran {{ $peran->label() }} tidak boleh membuka halaman ini.
                @else
                    Akun Anda tidak boleh membuka halaman ini.
                @endif
            </p>
            <a href="{{ $beranda }}"
               class="mt-5 inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">
                Ke halaman saya
            </a>
        </div>
    </x-app-layout>
@else
    <x-guest-layout>
        <div class="w-full max-w-sm text-center">
            <h1 class="text-lg font-bold">Akses ditolak</h1>
            <p class="mt-1 text-sm text-mute">Anda tidak boleh membuka halaman ini.</p>
            <a href="{{ $beranda }}"
               class="mt-5 inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">
                Ke halaman login
            </a>
        </div>
    </x-guest-layout>
@endauth
