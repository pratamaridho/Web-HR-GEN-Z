{{-- Satu tab per batch; daftar peserta tampil rapat dalam kolom --}}
@php
    $adaTanpa = $tanpaBatch->isNotEmpty();
    $awal = $kartu->first()?->id_batch ?? ($adaTanpa ? 0 : -1);
    $labelChip = fn ($b) => $b->skema->nama_skema.($periode ? '' : ' · '.\Carbon\Carbon::createFromFormat('!Y-m', $b->periode)->translatedFormat('M Y'));
@endphp

@if ($kartu->isEmpty() && ! $adaTanpa)
    <div class="mt-4 rounded-md border border-line bg-white p-8 text-center text-mute">
        Belum ada batch atau peserta yang cocok. Klik "Tambah peserta" atau "Tempel banyak".
    </div>
@else
    <div class="mt-4" x-data="{ aktif: {{ $awal }} }">
        <div class="flex flex-wrap gap-2" role="tablist">
            @foreach ($kartu as $b)
                <button type="button" role="tab" @click="aktif = {{ $b->id_batch }}"
                        :class="aktif === {{ $b->id_batch }} ? 'border-cobalt bg-cobalt text-white' : 'border-line bg-white text-navy hover:border-cobalt'"
                        class="inline-flex h-10 items-center gap-2 rounded-md border px-3 text-sm font-semibold">
                    {{ $labelChip($b) }}
                    <span class="rounded bg-white/20 px-1.5 text-xs">{{ $b->peserta_count }}</span>
                </button>
            @endforeach
            @if ($adaTanpa)
                <button type="button" role="tab" @click="aktif = 0"
                        :class="aktif === 0 ? 'border-warn bg-warn text-white' : 'border-warn/50 bg-warnbg text-warn'"
                        class="inline-flex h-10 items-center gap-2 rounded-md border px-3 text-sm font-semibold">
                    Belum masuk batch
                    <span class="rounded bg-white/30 px-1.5 text-xs">{{ $tanpaBatch->count() }}</span>
                </button>
            @endif
        </div>

        @foreach ($kartu as $b)
            <section x-show="aktif === {{ $b->id_batch }}" @if ($b->id_batch !== $awal) x-cloak @endif
                     class="mt-3 rounded-md border border-line bg-white" role="tabpanel">
                @php $menunggu = $b->peserta->where('pivot.status', 'Registered'); @endphp
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3">
                    <div>
                        <h2 class="font-bold text-navy">{{ $b->skema->nama_lengkap }}</h2>
                        <p class="text-xs text-mute">
                            {{ \Carbon\Carbon::createFromFormat('!Y-m', $b->periode)->translatedFormat('F Y') }} · {{ $b->mode }} · {{ $b->peserta_count }} peserta
                            @if ($menunggu->isNotEmpty()) · <span class="font-semibold text-warn">{{ $menunggu->count() }} menunggu validasi</span> @endif
                        </p>
                    </div>
                    @if ($menunggu->isNotEmpty())
                        <form method="POST" action="{{ route('peserta.validasi') }}">
                            @csrf
                            <input type="hidden" name="id_batch" value="{{ $b->id_batch }}">
                            <button type="submit" class="h-9 rounded-md bg-cobalt px-3 text-sm font-semibold text-white hover:bg-cobalt-dark">Validasi semua</button>
                        </form>
                    @endif
                </div>
                @if ($b->peserta->isEmpty())
                    <p class="px-4 py-6 text-sm text-mute">Belum ada peserta. Pakai "Masukkan ke batch".</p>
                @else
                    <ul class="grid gap-x-6 px-4 py-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($b->peserta as $p)
                            <li class="flex items-center justify-between gap-2 border-b border-line py-2 text-sm last:border-0">
                                <span class="min-w-0">
                                    <a href="{{ route('peserta.alur', $p) }}" class="block truncate font-medium text-navy hover:underline">{{ $p->nama }}</a>
                                    <span class="block truncate text-xs text-mute">{{ $p->email }}</span>
                                </span>
                                @if ($p->pivot->status === 'Registered')
                                    <form method="POST" action="{{ route('peserta.validasi') }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="id_batch" value="{{ $b->id_batch }}">
                                        <input type="hidden" name="id_peserta[]" value="{{ $p->id_peserta }}">
                                        <button type="submit" class="rounded-md border border-warn/50 bg-warnbg px-2 py-1 text-xs font-semibold text-warn hover:border-warn">Validasi</button>
                                    </form>
                                @else
                                    <x-badge>{{ $p->pivot->status === 'Validated' ? 'Tervalidasi' : $p->pivot->status }}</x-badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        @if ($adaTanpa)
            <section x-show="aktif === 0" @if ($awal !== 0) x-cloak @endif
                     class="mt-3 rounded-md border border-warn/40 bg-white" role="tabpanel">
                <div class="border-b border-warn/30 bg-warnbg px-4 py-3 text-sm text-warn">
                    <span class="font-semibold">{{ $tanpaBatch->count() }} peserta belum masuk batch.</span>
                    Pakai "Masukkan ke batch" agar mereka ikut sesi.
                </div>
                <ul class="grid gap-x-6 px-4 py-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tanpaBatch as $p)
                        <li class="border-b border-line py-2 text-sm last:border-0">
                            <span class="block truncate font-medium">{{ $p->nama }}</span>
                            <span class="block truncate text-xs text-mute">{{ $p->email }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endif
