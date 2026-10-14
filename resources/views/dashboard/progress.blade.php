            {{-- 7: progres per skema --}}
            @php $hariProgres = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ','); @endphp
            <section class="{{ $kartu }} lg:col-span-2">
                <h2 class="{{ $judul }}">Progres skema</h2>

                <ul class="mt-4 space-y-4">
                    @foreach ($r['progress'] as $p)
                        <li class="flex items-center gap-4">
                            <span class="w-14 shrink-0 text-sm font-semibold">{{ $p['skema'] }}</span>

                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-sky" role="progressbar"
                                 aria-valuenow="{{ min(100, $p['persen_terjadwal']) }}" aria-valuemin="0" aria-valuemax="100"
                                 aria-label="Terjadwal {{ $p['skema'] }}">
                                <div class="bar-isi h-full rounded-full bg-cobalt" style="--lebar: {{ min(100, $p['persen_terjadwal']) }}%"></div>
                            </div>

                            <span class="w-28 shrink-0 text-right text-sm">
                                <strong>{{ $hariProgres($p['terjadwal']) }}/{{ $hariProgres($p['wajib']) }}</strong>
                                <span class="text-xs text-mute">hari</span>
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if ($r['progress']->contains(fn ($p) => $p['selesai'] > 0))
                    <p class="mt-4 text-xs text-mute">
                        Selesai:
                        {{ $r['progress']->filter(fn ($p) => $p['selesai'] > 0)->map(fn ($p) => $p['skema'].' '.$hariProgres($p['selesai']).' hari')->implode(' · ') }}
                    </p>
                @endif
            </section>
