            {{-- 9: kehadiran --}}
            <section class="{{ $kartu }}">
                <h2 class="{{ $judul }}">Kehadiran</h2>

                @if ($k['total'] === 0)
                    <p class="mt-3 text-sm text-mute">Belum ada yang tercatat.</p>
                @else
                    <div class="mt-4 flex h-2 overflow-hidden rounded-full bg-sky" role="img"
                         aria-label="Hadir {{ $k['persen_hadir'] }}%, izin {{ $k['persen_izin'] }}%, alpa {{ $k['persen_alpa'] }}%">
                        <div class="bg-cobalt" style="width: {{ $k['persen_hadir'] }}%"></div>
                        <div class="bg-mute/50" style="width: {{ $k['persen_izin'] }}%"></div>
                        <div class="bg-bad" style="width: {{ $k['persen_alpa'] }}%"></div>
                    </div>

                    <dl class="mt-4 grid grid-cols-3 gap-2">
                        @foreach ([
                            'Hadir' => ['persen_hadir', 'hadir'],
                            'Izin' => ['persen_izin', 'izin'],
                            'Alpa' => ['persen_alpa', 'alpa'],
                        ] as $label => [$persen, $jumlah])
                            <div>
                                <dt class="text-xs text-mute">{{ $label }} ({{ $k[$jumlah] }})</dt>
                                <dd class="mt-0.5 text-xl font-semibold" data-hitung="{{ $k[$persen] }}" data-akhiran="%">{{ $k[$persen] }}%</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>
