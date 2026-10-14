@php
    $jam = fn ($t) => str_replace(':', '.', substr($t, 0, 5));
    $pilihanLama = (array) old('kehadiran', []);
    $terkunci = $sesi->kehadiranTerkunci();
    // Peserta dikelompokkan per batch yang dilayani sesi
    $kelompok = $sesi->batches
        ->map(fn ($b) => ['batch' => $b, 'peserta' => $peserta->filter(fn ($p) => $p->batches->contains('id_batch', $b->id_batch))->values()])
        ->filter(fn ($g) => $g['peserta']->isNotEmpty())
        ->values();
@endphp

<x-app-layout title="Attendance">
    <a href="{{ route('jadwal-saya') }}" class="text-sm font-semibold text-cobalt hover:underline">← Kembali ke jadwal</a>

    <h1 class="mt-3 text-2xl font-bold text-navy">{{ $sesi->nama_sesi }}</h1>
    <p class="mt-1 text-sm text-mute">
        {{ $sesi->tanggal->translatedFormat('l, j M Y') }} · {{ $jam($sesi->jam_mulai) }}–{{ $jam($sesi->jam_selesai) }} · {{ $sesi->room }}
        · {{ $sesi->batches->pluck('skema.nama_skema')->unique()->implode(' ') }} · {{ $sesi->tipe }}
    </p>

    @if (session('sukses'))
        <div role="status" class="mt-4 rounded-md bg-okbg p-3 text-sm text-ok">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-md bg-badbg p-3 text-sm text-bad">
            Kehadiran belum tersimpan. {{ $errors->first() }}
        </div>
    @endif
    @if ($terkunci)
        <div class="mt-4 rounded-md bg-warnbg p-3 text-sm text-warn">
            {{ $sesi->status === 'Cancelled' ? 'Sesi dibatalkan: kehadiran tidak dicatat.' : 'Batas pencatatan 24 jam sudah lewat. Minta Admin untuk koreksi.' }}
        </div>
    @endif

    @if ($peserta->isEmpty())
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center">
            <h2 class="text-lg font-bold">Belum ada peserta</h2>
            <p class="mt-1 text-sm text-mute">Minta Admin mendaftarkan peserta ke batch sesi ini.</p>
        </div>
    @else
        <form method="POST" action="{{ route('kehadiran.simpan', $sesi) }}" class="mt-6"
              x-data="{ terisi: 0, hitung() { this.terisi = new Set([...this.$el.querySelectorAll('input[type=radio]:checked')].map(r => r.name)).size } }"
              x-init="hitung()" @change="hitung()">
            @csrf

            @unless ($terkunci)
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <button type="button"
                            @click="$el.closest('form').querySelectorAll('input[type=radio][value=Hadir]').forEach(r => r.checked = true); hitung()"
                            class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy hover:border-cobalt">
                        Tandai semua Hadir
                    </button>
                    <span class="text-sm text-mute">Lalu ubah yang Izin atau Alpa saja.</span>
                </div>
            @endunless

            <div x-data="{ aktif: {{ $kelompok->first()['batch']->id_batch }} }">
                @if ($kelompok->count() > 1)
                    <div class="mb-3 flex flex-wrap gap-2" role="tablist">
                        @foreach ($kelompok as $g)
                            <button type="button" role="tab" @click="aktif = {{ $g['batch']->id_batch }}"
                                    :class="aktif === {{ $g['batch']->id_batch }} ? 'border-cobalt bg-cobalt text-white' : 'border-line bg-white text-navy hover:border-cobalt'"
                                    class="inline-flex h-10 items-center gap-2 rounded-md border px-3 text-sm font-semibold">
                                {{ $g['batch']->skema->nama_skema }}
                                <span class="rounded bg-white/20 px-1.5 text-xs">{{ $g['peserta']->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @include('kehadiran.kelompok')
            </div>

            <div class="sticky bottom-0 mt-4 flex items-center justify-between gap-3 border-t border-line bg-mist/95 py-3">
                <span class="text-sm text-mute">Terisi <span x-text="terisi"></span> dari {{ $peserta->count() }} peserta</span>
                @unless ($terkunci)
                    <button type="submit" class="h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Simpan kehadiran</button>
                @endunless
            </div>
        </form>
    @endif
</x-app-layout>
