@php
    // 3.0 -> "3", 5.5 -> "5,5"
    $hari = fn ($v) => rtrim(rtrim(number_format((float) abs($v), 1, ',', ''), '0'), ',');
    $semuaOk = $baris->isNotEmpty() && $jumlahOk === $baris->count();
@endphp

<x-app-layout title="Validasi durasi">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-navy">Validasi durasi</h1>
            <p class="mt-1 text-sm text-mute">Durasi terjadwal harus sama dengan durasi wajib.</p>
        </div>

        <form method="GET" action="{{ route('validasi-durasi') }}" class="flex items-center gap-2">
            <label class="text-sm font-medium" for="periode">Periode</label>
            <select id="periode" name="periode" onchange="this.form.submit()"
                    class="h-10 rounded-md border border-line bg-white px-3 text-sm">
                @foreach ($daftarPeriode as $p)
                    <option value="{{ $p }}" @selected($p === $periode)>{{ \Carbon\Carbon::createFromFormat('!Y-m', $p)->translatedFormat('F Y') }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($baris->isEmpty())
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center">
            <h2 class="text-lg font-bold">Belum ada sesi</h2>
            <p class="mt-1 text-sm text-mute">Buat sesi dulu, lalu durasinya dicek di sini.</p>
            <a href="{{ route('form-sesi') }}" class="mt-4 inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white">Tambah sesi</a>
        </div>
    @else
        <p class="mt-4 inline-flex rounded-md px-3 py-1.5 text-sm font-semibold {{ $semuaOk ? 'bg-okbg text-ok' : 'bg-warnbg text-warn' }}">
            {{ $jumlahOk }} dari {{ $baris->count() }} skema sesuai
        </p>

        <div class="mt-4 overflow-x-auto rounded-md border border-line bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-sky text-navy"><tr>
                    <th class="px-4 py-3 font-semibold">Skema</th>
                    <th class="px-4 py-3 font-semibold">Mode</th>
                    <th class="px-4 py-3 font-semibold">Wajib</th>
                    <th class="px-4 py-3 font-semibold">Terjadwal</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                </tr></thead>
                <tbody>
                    @foreach ($baris as $r)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-medium">{{ $r['skema'] }}</td>
                            <td class="px-4 py-3">{{ $r['mode'] }}</td>
                            <td class="px-4 py-3">{{ $hari($r['wajib']) }} hari</td>
                            <td class="px-4 py-3">{{ $hari($r['terjadwal']) }} hari</td>
                            <td class="px-4 py-3">
                                <x-badge>{{ $r['ok'] ? 'Sesuai' : ($r['selisih'] < 0 ? 'Kurang' : 'Lebih') }}</x-badge>
                                @unless ($r['ok'])
                                    <span class="ml-2 text-mute">{{ $hari($r['selisih']) }} hari</span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if (! empty($integrasi))
        <section class="mt-6 rounded-md border border-line bg-white p-5">
            <h2 class="text-base font-bold">Penggabungan skema</h2>
            @foreach ($integrasi as $mode => $cek)
                <p class="mt-3 text-xs font-semibold text-mute">{{ $mode }}</p>
                <ul class="mt-1 space-y-1.5 text-sm">
                    @foreach ($cek as $c)
                        <li class="flex flex-wrap items-center gap-2">
                            <x-badge>{{ $c['ok'] === null ? 'Menunggu' : ($c['ok'] ? 'Sesuai' : 'Belum') }}</x-badge>
                            <span class="font-medium">{{ $c['aturan'] }}</span>
                            <span class="text-mute">{{ $c['pesan'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </section>
    @endif
</x-app-layout>
