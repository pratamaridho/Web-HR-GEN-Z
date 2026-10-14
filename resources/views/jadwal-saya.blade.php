@php
    $jam = fn ($t) => str_replace(':', '.', substr($t, 0, 5));
@endphp

<x-app-layout title="Jadwal saya">
    <div>
            <h1 class="text-2xl font-bold text-navy">Jadwal saya</h1>
            <p class="mt-1 text-sm text-mute">Sesi yang Anda ajar.</p>
        </div>

    @if ($sesi->isEmpty())
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center">
            <h2 class="text-lg font-bold">Belum ada sesi</h2>
            <p class="mt-1 text-sm text-mute">Admin belum menugaskan sesi ke Anda.</p>
        </div>
    @else
        <div class="mt-6 overflow-x-auto rounded-md border border-line bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-sky text-navy"><tr>
                    <th class="px-4 py-3 font-semibold">Tanggal</th>
                    <th class="px-4 py-3 font-semibold">Jam</th>
                    <th class="px-4 py-3 font-semibold">Sesi</th>
                    <th class="px-4 py-3 font-semibold">Skema</th>
                    <th class="px-4 py-3 font-semibold">Peserta</th>
                    <th class="px-4 py-3 font-semibold">Room</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody>
                    @foreach ($sesi as $s)
                        <tr class="border-t border-line {{ $s->tanggal->isToday() ? 'bg-sky/60' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3">{{ $s->tanggal->translatedFormat('D j M') }} @if ($s->tanggal->isToday()) <x-badge class="bg-cobalt text-white">Hari ini</x-badge> @endif</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $jam($s->jam_mulai) }}–{{ $jam($s->jam_selesai) }}</td>
                            <td class="px-4 py-3 font-medium">{{ $s->nama_sesi }}</td>
                            <td class="px-4 py-3">{{ $s->batches->pluck('skema.nama_skema')->unique()->implode(' ') }}</td>
                            <td class="px-4 py-3">{{ $jumlahPeserta[$s->id_sesi] }}</td>
                            <td class="px-4 py-3">{{ $s->room }}</td>
                            <td class="px-4 py-3"><x-badge>{{ $s->status }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('kehadiran.form', $s) }}" class="inline-flex h-9 items-center rounded-md border border-line px-3 font-semibold text-cobalt hover:border-cobalt">Catat kehadiran</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-app-layout>
