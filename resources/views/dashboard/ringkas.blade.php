        {{-- 1-4 dan 8: periode, batch, peserta, sesi, konflik --}}
        <dl class="berurutan mt-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div class="{{ $kartu }}">
                <dt class="text-sm text-mute">Periode aktif</dt>
                <dd class="mt-1 text-xl font-semibold text-ink">{{ $namaPeriode }}</dd>
            </div>

            @foreach ([
                'Batch aktif' => $r['batch_aktif'],
                'Peserta' => $r['peserta'],
                'Sesi' => $r['sesi'],
                'Konflik' => $r['konflik'],
            ] as $label => $nilai)
                <div class="{{ $kartu }}">
                    <dt class="flex items-center gap-2 text-sm text-mute">
                        {{ $label }}
                        @if ($label === 'Konflik')
                            <span class="h-2 w-2 rounded-full {{ $nilai === 0 ? 'bg-ok' : 'bg-bad' }}" aria-hidden="true"></span>
                        @endif
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-ink" data-hitung="{{ $nilai }}">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>
