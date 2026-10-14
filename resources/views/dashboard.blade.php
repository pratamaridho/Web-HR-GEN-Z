@php
    $jam = fn ($t) => str_replace(':', '.', substr($t, 0, 5));
    $kartu = 'rounded-md border border-line bg-white p-5';
    $judul = 'text-sm font-semibold text-ink';
    $adalahAdmin = auth()->user()->can('manage-operations');
    $namaPeriode = $r['periode'] ? \Carbon\Carbon::createFromFormat('!Y-m', $r['periode'])->translatedFormat('F Y') : null;
    $k = $r['kehadiran'];
@endphp

<x-app-layout title="Dashboard">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-navy">Dashboard</h1>

        @if ($adalahAdmin && $namaPeriode)
            <a href="{{ route('form-sesi') }}"
               class="inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Tambah sesi</a>
        @endif
    </div>

    @if ($r['periode'] === null)
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center">
            <h2 class="text-lg font-bold">Belum ada batch</h2>
            <p class="mt-1 text-sm text-mute">Buat batch di Batch Management.</p>
        </div>
    @else
        @include('dashboard.ringkas')

        @include('dashboard.tindakan')

        @include('dashboard.jadwal')

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            @include('dashboard.progress')

            @include('dashboard.kehadiran')
        </div>
    @endif
</x-app-layout>
