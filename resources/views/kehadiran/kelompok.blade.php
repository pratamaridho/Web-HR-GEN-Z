                @foreach ($kelompok as $g)
                    <section x-show="aktif === {{ $g['batch']->id_batch }}" @if (! $loop->first) x-cloak @endif
                             class="rounded-md border border-line bg-white" role="tabpanel">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-sky px-4 py-3">
                            <div>
                                <h2 class="font-bold text-navy">{{ $g['batch']->skema->nama_lengkap }}</h2>
                                <p class="text-xs text-mute">{{ $g['peserta']->count() }} peserta</p>
                            </div>
                            @unless ($terkunci)
                                <button type="button"
                                        @click="$el.closest('section').querySelectorAll('input[type=radio][value=Hadir]').forEach(r => r.checked = true); $dispatch('change')"
                                        class="h-9 rounded-md border border-line bg-white px-3 text-sm font-semibold text-navy hover:border-cobalt">
                                    Tandai batch ini Hadir
                                </button>
                            @endunless
                        </div>
                        <ul class="grid gap-x-6 px-4 sm:grid-cols-2">
                            @foreach ($g['peserta'] as $p)
                                @php $nilai = $pilihanLama[$p->id_peserta] ?? ($tercatat[$p->id_peserta] ?? null); @endphp
                                <li class="flex flex-wrap items-center justify-between gap-2 border-b border-line py-2.5 last:border-0">
                                    <span class="font-medium">{{ $p->nama }}</span>
                                    <span class="flex gap-1.5">
                                        @foreach ($pilihan as $opsi)
                                            <label class="inline-flex h-9 min-w-[3.5rem] cursor-pointer items-center justify-center gap-1 rounded-md border border-line px-2 text-sm has-[:checked]:border-cobalt has-[:checked]:bg-sky has-[:checked]:font-semibold">
                                                <input type="radio" name="kehadiran[{{ $p->id_peserta }}]" value="{{ $opsi }}" @checked($nilai === $opsi)
                                                       @disabled($terkunci) class="border-line text-cobalt focus:ring-cobalt">
                                                {{ $opsi }}
                                            </label>
                                        @endforeach
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
