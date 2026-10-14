@php
    $angka = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
    $tab = fn ($m) => $m === $mode ? 'bg-cobalt text-white border-cobalt' : 'bg-white text-navy border-line';
    $namaPeriode = fn ($p) => \Carbon\Carbon::createFromFormat('!Y-m', $p)->translatedFormat('F Y');
@endphp

<x-app-layout title="Master Schedule">
    <div>
        <h1 class="text-2xl font-bold text-navy">Master Schedule</h1>
        <p class="mt-1 text-sm text-mute">Jadwal lengkap per periode untuk skenario Online atau Offline.</p>
    </div>

    <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
        <div class="flex gap-2" role="tablist">
            <a href="{{ route('master-schedule', ['mode' => 'Online']) }}" role="tab" aria-selected="{{ $mode === 'Online' ? 'true' : 'false' }}"
               class="inline-flex h-10 items-center rounded-md border px-4 text-sm font-semibold {{ $tab('Online') }}">Online</a>
            <a href="{{ route('master-schedule', ['mode' => 'Offline']) }}" role="tab" aria-selected="{{ $mode === 'Offline' ? 'true' : 'false' }}"
               class="inline-flex h-10 items-center rounded-md border px-4 text-sm font-semibold {{ $tab('Offline') }}">Offline</a>
        </div>

        @if ($daftarPeriode->isNotEmpty())
            <form method="GET" action="{{ route('master-schedule') }}" class="flex items-end gap-2">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">Periode</span>
                    <select name="periode" onchange="this.form.submit()"
                            class="h-10 rounded-md border border-line bg-white px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25">
                        @foreach ($daftarPeriode as $p)
                            <option value="{{ $p }}" @selected($p === $periode)>{{ $namaPeriode($p) }}</option>
                        @endforeach
                    </select>
                </label>
                <noscript><button type="submit" class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy">Tampilkan</button></noscript>
            </form>
        @endif
    </div>

    @if (empty($baris))
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center">
            <h2 class="text-lg font-bold">Belum ada sesi {{ $mode }}@if ($periode) pada {{ $namaPeriode($periode) }}@endif</h2>
            <p class="mt-1 text-sm text-mute">Buat batch dan sesi dengan mode {{ $mode }} untuk melihat jadwalnya di sini.</p>
        </div>
    @else
        <ul class="mt-4 flex flex-wrap gap-2 text-sm">
            <li class="rounded-md bg-sky px-3 py-1.5 font-semibold text-navy">{{ $ringkasan['hari_pelaksanaan'] }} hari pelaksanaan</li>
            <li class="rounded-md bg-sky px-3 py-1.5 font-semibold text-navy">{{ $ringkasan['calendar_days'] }} calendar days</li>
            <li class="rounded-md bg-sky px-3 py-1.5 font-semibold text-navy">{{ $ringkasan['trainer'] }} trainer</li>
            <li class="rounded-md bg-sky px-3 py-1.5 font-semibold text-navy">{{ $ringkasan['room'] }} room</li>
        </ul>

        <div class="mt-4 overflow-x-auto rounded-md border border-line bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-sky text-navy"><tr>
                    <th class="px-4 py-3 font-semibold">Hari</th>
                    <th class="px-4 py-3 font-semibold">Waktu</th>
                    <th class="px-4 py-3 font-semibold">Sesi</th>
                    <th class="px-4 py-3 font-semibold">Skema</th>
                    <th class="px-4 py-3 font-semibold">Kelompok</th>
                    <th class="px-4 py-3 font-semibold">Trainer</th>
                    <th class="px-4 py-3 font-semibold">Room</th>
                    <th class="px-4 py-3 font-semibold">Jenis</th>
                    <th class="px-4 py-3 font-semibold">Durasi</th>
                    <th class="px-4 py-3 font-semibold">Catatan</th>
                </tr></thead>
                <tbody>
                    @foreach ($baris as $r)
                        <tr class="border-t border-line align-top">
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="font-semibold">Day {{ $r['hari'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $r['waktu'] }}</td>
                            <td class="px-4 py-3 font-medium">{{ $r['sesi'] }}</td>
                            <td class="px-4 py-3">{{ $r['skema'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $r['peserta'] }} peserta</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $r['trainer'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $r['room'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-badge>{{ $r['jenis'] }}</x-badge>
                                @if ($r['paralel']) <x-badge class="bg-warnbg text-warn">Parallel</x-badge> @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $angka($r['durasi_jam']) }} jam
                            </td>
                            <td class="px-4 py-3 text-mute">{{ $r['catatan'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('master-schedule.verifikasi')

        @include('master-schedule.integrasi')

    @endif
</x-app-layout>
