            {{-- Sesi per hari --}}
            @if ($sesi->isEmpty())
                <p class="border-t border-line px-4 py-4 text-sm text-mute">Batch ini belum punya sesi.</p>
            @else
                <div class="overflow-x-auto border-t border-line">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-sky text-navy"><tr>
                            <th class="px-4 py-2.5 font-semibold">Hari</th>
                            <th class="px-4 py-2.5 font-semibold">Tanggal dan jam</th>
                            <th class="px-4 py-2.5 font-semibold">Sesi</th>
                            <th class="px-4 py-2.5 font-semibold">Trainer · Room</th>
                            <th class="px-4 py-2.5 font-semibold">Status sesi</th>
                            <th class="px-4 py-2.5 font-semibold">Kehadiran</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($sesi as $s)
                                <tr class="border-t border-line">
                                    <td class="whitespace-nowrap px-4 py-2.5 font-semibold">Day {{ $hari->search($s->tanggal->toDateString()) + 1 }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5">{{ $s->tanggal->translatedFormat('D, j M Y') }} · {{ $jam($s->jam_mulai) }}–{{ $jam($s->jam_selesai) }}</td>
                                    <td class="px-4 py-2.5 font-medium">{{ $s->nama_sesi }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5">{{ $s->trainer->nama }} · {{ $s->room }}</td>
                                    <td class="px-4 py-2.5"><x-badge>{{ $s->status }}</x-badge></td>
                                    <td class="px-4 py-2.5">
                                        @if (isset($kehadiran[$s->id_sesi]))
                                            <x-badge>{{ $kehadiran[$s->id_sesi] }}</x-badge>
                                        @else
                                            <span class="text-mute">{{ $status === 'Registered' ? 'Menunggu validasi' : 'Belum dicatat' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
