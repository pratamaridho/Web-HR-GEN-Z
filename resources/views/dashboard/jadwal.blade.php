        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            {{-- 5: sesi mendatang --}}
            <section class="{{ $kartu }} lg:col-span-2">
                <div class="flex items-baseline justify-between">
                    <h2 class="{{ $judul }}">Sesi mendatang</h2>
                    @if ($adalahAdmin && $r['mendatang']->isNotEmpty())
                        <a href="{{ route('jadwal-sesi') }}" class="text-sm font-semibold text-cobalt hover:underline">Lihat semua</a>
                    @endif
                </div>

                <ul class="mt-3 divide-y divide-line">
                    @forelse ($r['mendatang']->take(5) as $s)
                        @php $skema = $s->batches->pluck('skema.nama_skema')->unique()->values(); @endphp
                        <li class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">
                            <span class="w-16 shrink-0 text-sm font-semibold text-navy">{{ $s->tanggal->translatedFormat('D j') }}</span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $s->nama_sesi }}</p>
                                <p class="text-xs text-mute">{{ $jam($s->jam_mulai) }}–{{ $jam($s->jam_selesai) }} · {{ $s->trainer->nama }} · {{ $s->room }}</p>
                            </div>

                            <span class="shrink-0 text-xs text-mute">{{ $skema->count() === 6 ? 'Semua skema' : $skema->implode(' · ') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-mute">Belum ada.</li>
                    @endforelse
                </ul>
            </section>

            {{-- 6: alokasi trainer --}}
            <section class="{{ $kartu }}">
                <h2 class="{{ $judul }}">Alokasi trainer</h2>

                <ul class="mt-3 divide-y divide-line">
                    @forelse ($r['alokasi'] as $a)
                        <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-sky text-xs font-bold text-navy" aria-hidden="true">
                                {{ collect(explode(' ', $a['trainer']))->map(fn ($kata) => mb_substr($kata, 0, 1))->take(2)->implode('') }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $a['trainer'] }}</span>
                            <span class="shrink-0 text-xs text-mute">{{ $a['sesi'] }} sesi · {{ $a['hari'] }} hari</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-mute">Belum ada.</li>
                    @endforelse
                </ul>
            </section>
        </div>
