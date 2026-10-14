@php
    $tautan = fn ($b) => route('jadwal-sesi', $filter + ['tampil' => 'kalender', 'bulan' => $b]);
    $detail = fn ($s) => [
        'nama' => $s->nama_sesi,
        'tanggal' => $s->tanggal->translatedFormat('l, j F Y'),
        'jam' => $jam($s->jam_mulai).'–'.$jam($s->jam_selesai),
        'skema' => $s->batches->pluck('skema.nama_lengkap')->unique()->values()->all(),
        'peserta' => $kalender['pesertaPerSesi'][$s->id_sesi] ?? 0,
        'jenis' => $s->tipe,
        'paralel' => isset($paralel[$s->tanggal->toDateString().'|'.$s->jam_mulai]),
        'trainer' => $s->trainer->nama,
        'room' => $s->room,
        'status' => $s->status,
        'catatan' => $s->catatan,
        'ubah' => route('form-sesi', $s),
    ];
@endphp

<div x-data="{ buka: false, sesi: { nama: '', tanggal: '', jam: '', skema: [], peserta: 0, jenis: '', paralel: false, trainer: '', room: '', status: '', catatan: '', ubah: '#' } }" @keydown.escape.window="buka = false">
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ $tautan($kalender['sebelumnya']) }}" aria-label="Bulan sebelumnya"
               class="grid h-10 w-10 place-items-center rounded-md border border-line bg-white text-navy hover:border-cobalt">&lsaquo;</a>
            <h2 class="min-w-40 text-center text-lg font-bold text-navy">{{ $kalender['label'] }}</h2>
            <a href="{{ $tautan($kalender['berikutnya']) }}" aria-label="Bulan berikutnya"
               class="grid h-10 w-10 place-items-center rounded-md border border-line bg-white text-navy hover:border-cobalt">&rsaquo;</a>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-xs text-mute">
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-sky ring-1 ring-cobalt/40"></span> Common: sesi bersama</span>
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-white ring-1 ring-line"></span> Exclusive: satu skema</span>
            @if ($bulan !== $kalender['bulanIni'])
                <a href="{{ $tautan($kalender['bulanIni']) }}" class="font-semibold text-cobalt hover:underline">Bulan ini</a>
            @endif
        </div>
    </div>

    <div class="mt-3 overflow-x-auto rounded-md border border-line bg-white">
        <div class="min-w-[46rem]">
            <div class="grid grid-cols-7 bg-sky text-center text-xs font-semibold text-navy">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $nama)
                    <div class="py-2">{{ $nama }}</div>
                @endforeach
            </div>

            @foreach ($kalender['minggu'] as $minggu)
                <div class="grid grid-cols-7 border-t border-line">
                    @foreach ($minggu as $hari)
                        <div class="min-h-28 border-l border-line p-1.5 first:border-l-0 {{ $hari['dalamBulan'] ? '' : 'bg-mist/70' }}">
                            <span class="inline-grid h-6 min-w-6 place-items-center rounded-full px-1 text-xs font-semibold
                                {{ $hari['hariIni'] ? 'bg-cobalt text-white' : ($hari['dalamBulan'] ? 'text-navy' : 'text-mute') }}">{{ $hari['tanggal']->day }}</span>

                            @foreach ($hari['sesi'] as $s)
                                <button type="button" @click="sesi = @js($detail($s)); buka = true"
                                        aria-label="{{ $s->nama_sesi }}, {{ $jam($s->jam_mulai) }}"
                                        class="mt-1 block w-full truncate rounded px-1.5 py-1 text-left text-xs font-medium transition-colors
                                            {{ $s->status === 'Cancelled' ? 'line-through opacity-60' : '' }} {{ $s->tipe === 'Common' ? 'bg-sky text-cobalt ring-1 ring-cobalt/30 hover:bg-cobalt hover:text-white' : 'bg-white text-navy ring-1 ring-line hover:bg-mist' }}">
                                    {{ $jam($s->jam_mulai) }} {{ $s->nama_sesi }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    @if ($kalender['jumlah'] === 0)
        <p class="mt-3 text-sm text-mute">
            {{ $adaFilter ? 'Tidak ada sesi untuk filter ini pada bulan ' : 'Belum ada sesi pada bulan ' }}{{ $kalender['label'] }}.
        </p>
    @endif

    @include('jadwal-sesi.detail')
</div>
