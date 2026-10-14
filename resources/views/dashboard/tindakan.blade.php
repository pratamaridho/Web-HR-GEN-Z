        {{-- Perlu tindakan: hanya judul dan tombol; rinciannya ditelusuri di halaman tujuan --}}
        @php
            $t = $r['tindakan'];
            $kartuMerah = 'flex flex-wrap items-center justify-between gap-3 rounded-md border border-bad/30 bg-badbg p-5';
            $tombol = 'inline-flex h-9 items-center rounded-md border border-bad/40 bg-white px-3 text-sm font-semibold text-bad hover:bg-bad hover:text-white';
        @endphp

        @if ($t['jumlah'] === 0)
            <p class="mt-6 flex items-center gap-2 text-sm text-mute">
                <span class="h-2 w-2 rounded-full bg-ok" aria-hidden="true"></span>
                {{ $namaPeriode }}: semua siap
            </p>
        @else
            <h2 class="mt-6 text-sm font-semibold text-ink">{{ $namaPeriode }}: {{ $t['jumlah'] }} hal perlu tindakan</h2>

            <div class="mt-2 grid gap-3 {{ $t['jumlah'] > 1 ? 'lg:grid-cols-2' : '' }}">
                @if ($t['durasi'] !== [])
                    <section class="{{ $kartuMerah }}">
                        <h3 class="font-semibold text-bad">Durasi belum terpenuhi</h3>
                        @if ($adalahAdmin)
                            <a href="{{ route('validasi-durasi') }}" class="{{ $tombol }}">Atur jadwal</a>
                        @endif
                    </section>
                @endif

                @if ($t['batchKosong'] !== [])
                    <section class="{{ $kartuMerah }}">
                        <h3 class="font-semibold text-bad">Batch belum punya peserta</h3>
                        @if ($adalahAdmin)
                            <a href="{{ route('peserta') }}" class="{{ $tombol }}">Tambah peserta</a>
                        @endif
                    </section>
                @endif
            </div>
        @endif
