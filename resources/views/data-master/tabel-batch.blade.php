            @php
                // Satu bagian per bulan dan mode, terbaru di atas.
                $siklus = $batch->groupBy(fn ($b) => $b->periode.'|'.$b->mode)->sortKeysDesc();
                $angka = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
            @endphp

            <div class="mt-4 space-y-3">
                @forelse ($siklus as $isi)
                    @php
                        $perluTindakan = $isi->filter(fn ($b) => ! $ringkasan[$b->id_batch]['siap'])->count();
                    @endphp
                    <details class="rounded-md border border-line bg-white" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <span class="font-semibold">{{ $isi->first()->nama_siklus }}</span>
                            <span class="text-sm text-mute">
                                {{ $isi->count() }} skema ·
                                @if ($perluTindakan === 0) <span class="font-semibold text-ok">Semua siap</span>
                                @else <span class="font-semibold text-warn">{{ $perluTindakan }} perlu tindakan</span>
                                @endif
                            </span>
                        </summary>

                        <div class="overflow-x-auto border-t border-line">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-sky text-navy"><tr>
                                    <th class="px-4 py-2.5 font-semibold">Skema</th>
                                    <th class="px-4 py-2.5 font-semibold">Peserta</th>
                                    <th class="px-4 py-2.5 font-semibold">Durasi</th>
                                    <th class="px-4 py-2.5 font-semibold">Kesiapan</th>
                                    <th class="px-4 py-2.5 font-semibold">Status</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($isi->sortBy('id_skema') as $b)
                                        @php $r = $ringkasan[$b->id_batch]; @endphp
                                        <tr class="border-t border-line align-top">
                                            <td class="px-4 py-3">
                                                <span class="block font-medium">{{ $b->skema->nama_lengkap }}</span>
                                                <span class="text-xs text-mute">{{ $r['sesi'] }} sesi</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="font-semibold">{{ $r['valid'] }}</span> <span class="text-mute">/ {{ $r['peserta'] }}</span>
                                                <span class="block text-xs text-mute">tervalidasi</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="font-semibold">{{ $angka($r['terjadwal']) }}</span> <span class="text-mute">/ {{ $angka($r['wajib']) }} hari</span>
                                                <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-sky">
                                                    <div class="h-full rounded-full bg-cobalt" style="width: {{ $r['wajib'] > 0 ? min(100, round($r['terjadwal'] / $r['wajib'] * 100)) : 0 }}%"></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($r['siap'])
                                                    <x-badge class="bg-okbg text-ok">Siap</x-badge>
                                                @else
                                                    <x-badge class="bg-warnbg text-warn">Perlu tindakan</x-badge>
                                                    <ul class="mt-1 space-y-0.5 text-xs text-mute">
                                                        @foreach ($r['masalah'] as $m) <li>{{ $m }}</li> @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <form method="POST" action="{{ route('data-master.batch.status', $b) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status" aria-label="Status {{ $b->nama }}"
                                                            class="h-9 min-w-0 rounded-md border border-line bg-white px-2 text-sm">
                                                        @foreach (['Planned', 'Ongoing', 'Completed', 'Cancelled'] as $st)
                                                            <option value="{{ $st }}" @selected($b->status === $st)>{{ $st }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="h-9 rounded-md border border-line px-3 text-sm font-semibold text-navy">Simpan</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @empty
                    <div class="rounded-md border border-line bg-white p-6 text-center text-mute">Belum ada batch. Klik "Tambah batch" di atas.</div>
                @endforelse
            </div>
