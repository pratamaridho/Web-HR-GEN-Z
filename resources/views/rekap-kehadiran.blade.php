@php
    $kelas = 'h-10 rounded-md border border-line bg-white px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25';
    $namaPeriode = fn ($p) => \Carbon\Carbon::createFromFormat('!Y-m', $p)->translatedFormat('F Y');
@endphp

<x-app-layout title="Attendance">
    <div>
        <h1 class="text-2xl font-bold text-navy">Attendance</h1>
        <p class="mt-1 text-sm text-mute">Completed bila hadir minimal 80% dari sesi yang selesai.</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="mt-4 rounded-md bg-okbg p-3 text-sm text-ok">{{ session('sukses') }}</div>
    @endif

    <form method="GET" action="{{ route('rekap-kehadiran') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <label class="block">
            <span class="mb-1.5 block text-sm font-medium">Periode</span>
            <select name="periode" onchange="this.form.submit()" class="{{ $kelas }}">
                @foreach ($daftarPeriode as $p)
                    <option value="{{ $p }}" @selected($p === $periode)>{{ $namaPeriode($p) }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="mb-1.5 block text-sm font-medium">Cari</span>
            <input type="search" name="q" value="{{ $q }}" placeholder="Nama atau email" class="{{ $kelas }}">
        </label>
        <label class="block">
            <span class="mb-1.5 block text-sm font-medium">Batch</span>
            <select name="batch" onchange="this.form.submit()" class="{{ $kelas }}">
                <option value="">Semua batch</option>
                @foreach ($daftarBatch as $b)
                    <option value="{{ $b->id_batch }}" @selected($filterBatch === $b->id_batch)>{{ $b->nama }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy">Terapkan</button>
    </form>

    @if ($kelompok->isEmpty())
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center text-mute">
            Belum ada rekap. Muncul setelah peserta masuk batch dan trainer mencatat kehadiran.
        </div>
    @else
        <p class="mt-4 text-sm text-mute">
            {{ $periode ? $namaPeriode($periode).' · ' : 'Semua periode · ' }}{{ $kelompok->count() }} batch · {{ $kelompok->sum('jumlah') }} peserta
        </p>

        <div class="mt-3" x-data="{ aktif: {{ $kelompok->first()->id_batch }} }">
            <div class="flex flex-wrap gap-2" role="tablist">
                @foreach ($kelompok as $g)
                    <button type="button" role="tab" @click="aktif = {{ $g->id_batch }}"
                            :class="aktif === {{ $g->id_batch }} ? 'border-cobalt bg-cobalt text-white' : 'border-line bg-white text-navy hover:border-cobalt'"
                            class="inline-flex h-10 items-center gap-2 rounded-md border px-3 text-sm font-semibold">
                        {{ $g->kode }}{{ $periode ? '' : ' · '.\Carbon\Carbon::createFromFormat('!Y-m', $g->periode)->translatedFormat('M Y') }}
                        <span class="rounded bg-white/20 px-1.5 text-xs">{{ $g->rata_hadir }}%</span>
                    </button>
                @endforeach
            </div>

            @include('rekap-kehadiran.panel')
        </div>
    @endif
</x-app-layout>
