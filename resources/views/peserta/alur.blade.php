@php
    $jam = fn ($t) => str_replace(':', '.', substr($t, 0, 5));
    $langkah = [
        'Registered' => ['Terdaftar', 0],
        'Validated' => ['Divalidasi', 1],
        'Ongoing' => ['Berjalan', 2],
        'Completed' => ['Selesai', 3],
    ];
    $namaLangkah = ['Terdaftar', 'Divalidasi', 'Berjalan', 'Selesai'];
@endphp

<x-app-layout title="Alur peserta">
    <a href="{{ route('peserta') }}" class="text-sm font-semibold text-cobalt hover:underline">← Kembali ke Participant Mapping</a>

    <h1 class="mt-3 text-2xl font-bold text-navy">{{ $peserta->nama }}</h1>
    <p class="mt-1 text-sm text-mute">{{ $peserta->email }}</p>

    @if (session('sukses'))
        <div role="status" class="mt-4 rounded-md bg-okbg p-3 text-sm text-ok">{{ session('sukses') }}</div>
    @endif

    @forelse ($peserta->batches as $b)
        @php
            $status = $b->pivot->status;
            $posisi = $langkah[$status][1] ?? null;
            $sesi = $b->sesi->sortBy([['tanggal', 'asc'], ['jam_mulai', 'asc']])->values();
            $hari = $sesi->map(fn ($s) => $s->tanggal->toDateString())->unique()->values();
        @endphp

        <section class="mt-6 rounded-md border border-line bg-white">
            <div class="border-b border-line px-4 py-3">
                <h2 class="font-bold text-navy">{{ $b->skema->nama_lengkap }}</h2>
                <p class="text-xs text-mute">
                    {{ \Carbon\Carbon::createFromFormat('!Y-m', $b->periode)->translatedFormat('F Y') }} · {{ $b->mode }}
                </p>
            </div>

            {{-- Langkah: terdaftar, divalidasi, berjalan, selesai --}}
            <ol class="grid grid-cols-2 gap-2 px-4 py-4 sm:grid-cols-4">
                @foreach ($namaLangkah as $i => $nama)
                    @php $capai = $posisi !== null && $i <= $posisi; @endphp
                    <li class="rounded-md border px-3 py-2 text-sm {{ $capai ? 'border-cobalt bg-sky font-semibold text-navy' : 'border-line text-mute' }}">
                        <span class="block text-xs {{ $capai ? 'text-cobalt' : '' }}">Langkah {{ $i + 1 }}</span>
                        {{ $nama }}
                    </li>
                @endforeach
            </ol>
            @if ($status === 'Dropped')
                <p class="mx-4 mb-4 rounded-md bg-badbg p-3 text-sm text-bad">Peserta keluar dari batch ini (Dropped).</p>
            @elseif ($status === 'Registered')
                <div class="mx-4 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-md bg-warnbg p-3 text-sm text-warn">
                    <span>Menunggu validasi. Peserta belum dihitung pada kuota sesi dan belum muncul di daftar kehadiran.</span>
                    <form method="POST" action="{{ route('peserta.validasi') }}">
                        @csrf
                        <input type="hidden" name="id_batch" value="{{ $b->id_batch }}">
                        <input type="hidden" name="id_peserta[]" value="{{ $peserta->id_peserta }}">
                        <button type="submit" class="h-9 rounded-md bg-cobalt px-3 text-sm font-semibold text-white hover:bg-cobalt-dark">Validasi</button>
                    </form>
                </div>
            @endif

            @include('peserta.alur-sesi')
        </section>
    @empty
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center text-mute">
            Peserta ini belum masuk batch. Pakai "Masukkan ke batch" di Participant Mapping.
        </div>
    @endforelse
</x-app-layout>
