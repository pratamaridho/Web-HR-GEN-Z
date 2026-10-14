@php
    $namaPeriode = fn ($p) => \Carbon\Carbon::createFromFormat('!Y-m', $p)->translatedFormat('F Y');
    $totalPeserta = $kartu->sum('peserta_count');
@endphp

{{-- Filter --}}
<form method="GET" action="{{ route('peserta') }}" class="mt-6 flex flex-wrap items-end gap-3">
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
                <option value="{{ $b->id_batch }}" @selected($filterBatch === $b->id_batch)>{{ $labelBatch($b) }}</option>
            @endforeach
        </select>
    </label>
    <button type="submit" class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy">Terapkan</button>
</form>

<p class="mt-4 text-sm text-mute">
    {{ $periode ? $namaPeriode($periode).' · ' : 'Semua periode · ' }}{{ $kartu->count() }} batch · {{ $totalPeserta }} peserta
</p>
