    @forelse ($perHari as $tanggal => $daftar)
        <section class="mt-6">
            <h2 class="flex items-center gap-2 text-base font-bold">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, j F Y') }}
                @if (\Carbon\Carbon::parse($tanggal)->isToday()) <x-badge class="bg-cobalt text-white">Hari ini</x-badge> @endif</h2>
            <div class="mt-2 overflow-x-auto rounded-md border border-line bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sky text-navy"><tr>
                        <th class="px-4 py-3 font-semibold">Jam</th>
                        <th class="px-4 py-3 font-semibold">Sesi</th>
                        <th class="px-4 py-3 font-semibold">Skema</th>
                        <th class="px-4 py-3 font-semibold">Jenis</th>
                        <th class="px-4 py-3 font-semibold">Trainer · Room</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($daftar as $s)
                            <tr class="border-t border-line">
                                <td class="whitespace-nowrap px-4 py-3">{{ $jam($s->jam_mulai) }}–{{ $jam($s->jam_selesai) }}</td>
                                <td class="px-4 py-3"><span class="font-medium">{{ $s->nama_sesi }}</span>@if ($s->catatan) <span class="block text-xs text-mute">{{ $s->catatan }}</span> @endif</td>
                                <td class="px-4 py-3">{{ $s->batches->pluck('skema.nama_skema')->unique()->implode(' ') }}</td>
                                <td class="px-4 py-3"><x-badge>{{ $s->tipe }}</x-badge>@if (isset($paralel[$s->tanggal->toDateString().'|'.$s->jam_mulai])) <x-badge class="bg-warnbg text-warn">Parallel</x-badge> @endif</td>
                                <td class="px-4 py-3">{{ $s->trainer->nama }} · {{ $s->room }}</td>
                                <td class="px-4 py-3"><x-badge>{{ $s->status }}</x-badge></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('form-sesi', $s) }}" class="font-semibold text-cobalt hover:underline">Ubah</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="mt-6 rounded-md border border-line bg-white p-8 text-center">
            <h2 class="text-lg font-bold">{{ $adaFilter ? 'Tidak ada hasil' : 'Belum ada sesi' }}</h2>
            <p class="mt-1 text-sm text-mute">
                {{ $adaFilter ? 'Tidak ada sesi untuk filter ini.' : 'Klik Tambah sesi untuk membuat sesi pertama.' }}
            </p>
            @if ($adaFilter)
                <a href="{{ route('jadwal-sesi', ['tampil' => 'daftar']) }}" class="mt-4 inline-flex h-10 items-center rounded-md border border-line px-4 text-sm font-semibold text-navy">Reset filter</a>
            @endif
        </div>
    @endforelse
