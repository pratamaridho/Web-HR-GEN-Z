@php
    $jam = fn ($t) => str_replace(':', '.', substr($t, 0, 5));
    $lama = (array) old('kehadiran', []);
@endphp

<x-app-layout title="Koreksi kehadiran">
    <a href="{{ route('rekap-kehadiran') }}" class="text-sm font-semibold text-cobalt hover:underline">← Kembali ke rekap</a>

    <h1 class="mt-3 text-2xl font-bold text-navy">Koreksi kehadiran</h1>
    <p class="mt-1 text-sm text-mute">{{ $peserta->nama }} · {{ $batch->nama }}</p>

    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-md bg-badbg p-3 text-sm text-bad">{{ $errors->first() }}</div>
    @endif

    @if ($sesi->isEmpty())
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center text-sm text-mute">Batch ini belum punya sesi.</div>
    @else
        <form method="POST" action="{{ route('rekap-kehadiran.koreksi.simpan', [$peserta, $batch]) }}" class="mt-6">
            @csrf
            <div class="overflow-x-auto rounded-md border border-line bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sky text-navy"><tr>
                        <th class="px-4 py-3 font-semibold">Sesi</th>
                        <th class="px-4 py-3 font-semibold">Status sesi</th>
                        <th class="px-4 py-3 font-semibold">Kehadiran</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($sesi as $s)
                            @php
                                $sebelumnya = $tercatat[$s->id_sesi] ?? null;
                                $nilai = $lama[$s->id_sesi] ?? $sebelumnya;
                            @endphp
                            <tr class="border-t border-line">
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ $s->nama_sesi }}</p>
                                    <p class="text-xs text-mute">{{ $s->tanggal->translatedFormat('D j M') }} · {{ $jam($s->jam_mulai) }}–{{ $jam($s->jam_selesai) }}</p>
                                </td>
                                <td class="px-4 py-3"><x-badge>{{ $s->status }}</x-badge></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-4">
                                        @foreach ($pilihan as $opsi)
                                            <label class="inline-flex items-center gap-1.5">
                                                <input type="radio" name="kehadiran[{{ $s->id_sesi }}]" value="{{ $opsi }}" @checked($nilai === $opsi)
                                                       class="border-line text-cobalt focus:ring-cobalt">
                                                {{ $opsi }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-1 text-xs text-mute">{{ $sebelumnya ? 'Sebelumnya: '.$sebelumnya : 'Belum dicatat' }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" class="h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Simpan koreksi</button>
                <a href="{{ route('rekap-kehadiran') }}" class="inline-flex h-10 items-center rounded-md border border-line px-4 text-sm font-semibold text-navy">Batal</a>
            </div>
        </form>
    @endif
</x-app-layout>
