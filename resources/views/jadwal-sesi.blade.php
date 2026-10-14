@php
    $kelas = 'h-10 rounded-md border border-line bg-white px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25';
    $jam = fn ($t) => str_replace(':', '.', substr($t, 0, 5));
    $adaFilter = $filterSkema || $filterTrainer;
    $filter = array_filter(['skema' => $filterSkema, 'trainer' => $filterTrainer]);
    $tab = fn ($aktif) => $aktif ? 'bg-white text-navy shadow-sm' : 'text-mute hover:text-navy';
@endphp

<x-app-layout title="Training Schedule">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-navy">Training Schedule</h1>
            <p class="mt-1 text-sm text-mute">Sesi bentrok ditolak saat disimpan.</p>
        </div>
        <a href="{{ route('form-sesi') }}"
           class="inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Tambah sesi</a>
    </div>

    @if (session('sukses'))
        <div role="status" class="mt-4 rounded-md bg-okbg p-3 text-sm text-ok">{{ session('sukses') }}</div>
    @endif

    <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('jadwal-sesi') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tampil" value="{{ $tampil }}">
            @isset($bulan) <input type="hidden" name="bulan" value="{{ $bulan }}"> @endisset
            <label class="block">
                <span class="mb-1.5 block text-sm font-medium">Skema</span>
                <select name="skema" class="{{ $kelas }}">
                    <option value="">Semua skema</option>
                    @foreach ($daftarSkema as $s)
                        <option value="{{ $s->id_skema }}" @selected($filterSkema === $s->id_skema)>{{ $s->nama_skema }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-sm font-medium">Trainer</span>
                <select name="trainer" class="{{ $kelas }}">
                    <option value="">Semua trainer</option>
                    @foreach ($daftarTrainer as $t)
                        <option value="{{ $t->id_trainer }}" @selected($filterTrainer === $t->id_trainer)>{{ $t->nama }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy">Filter</button>
        </form>

        <nav aria-label="Tampilan" class="flex gap-1 rounded-md bg-sky p-1 text-sm font-semibold">
            <a href="{{ route('jadwal-sesi', $filter + ['tampil' => 'kalender']) }}" class="rounded px-3 py-1.5 {{ $tab($tampil === 'kalender') }}">Kalender</a>
            <a href="{{ route('jadwal-sesi', $filter + ['tampil' => 'daftar']) }}" class="rounded px-3 py-1.5 {{ $tab($tampil === 'daftar') }}">Daftar</a>
        </nav>
    </div>

    @if ($tampil === 'kalender')
        @include('jadwal-sesi.kalender')
    @else
        @include('jadwal-sesi.daftar')
    @endif
</x-app-layout>
