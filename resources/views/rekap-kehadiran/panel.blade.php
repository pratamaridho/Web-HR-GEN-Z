@foreach ($kelompok as $g)
    <section x-show="aktif === {{ $g->id_batch }}" @if (! $loop->first) x-cloak @endif
             class="mt-3 rounded-md border border-line bg-white" role="tabpanel">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h2 class="font-bold text-navy">{{ $g->skema }}</h2>
                <p class="text-xs text-mute">{{ $namaPeriode($g->periode) }} · {{ $g->mode }} · {{ $g->jumlah }} peserta</p>
            </div>
            <dl class="flex gap-6 text-sm">
                <div><dt class="text-xs text-mute">Rata-rata hadir</dt><dd class="text-lg font-bold text-navy">{{ $g->rata_hadir }}%</dd></div>
                <div><dt class="text-xs text-mute">Completed</dt><dd class="text-lg font-bold text-navy">{{ $g->selesai }} / {{ $g->jumlah }}</dd></div>
                <div><dt class="text-xs text-mute">Sesi selesai</dt><dd class="text-lg font-bold text-navy">{{ $g->sesi_selesai }}</dd></div>
            </dl>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-sky text-navy"><tr>
                    <th class="px-4 py-2.5 font-semibold">Peserta</th>
                    <th class="px-4 py-2.5 font-semibold">Hadir</th>
                    <th class="px-4 py-2.5 font-semibold">Izin</th>
                    <th class="px-4 py-2.5 font-semibold">Alpa</th>
                    <th class="px-4 py-2.5 font-semibold" title="Hadir terhadap sesi berstatus Completed">Hadir %</th>
                    <th class="px-4 py-2.5 font-semibold">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr></thead>
                <tbody>
                    @foreach ($g->peserta as $r)
                        <tr class="border-t border-line">
                            <td class="px-4 py-2.5 font-medium">{{ $r->nama }}</td>
                            <td class="px-4 py-2.5">{{ $r->hadir }}</td>
                            <td class="px-4 py-2.5">{{ $r->izin }}</td>
                            <td class="px-4 py-2.5">{{ $r->alpa }}</td>
                            <td class="px-4 py-2.5">{{ $r->persen }}%</td>
                            <td class="px-4 py-2.5"><x-badge>{{ $r->status }}</x-badge></td>
                            <td class="px-4 py-2.5 text-right">
                                <a href="{{ route('rekap-kehadiran.koreksi', [$r->id_peserta, $r->id_batch]) }}"
                                   class="font-semibold text-cobalt hover:underline">Koreksi</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endforeach
