        @php
            // Pilihan siap pakai sesuai aturan bisnis: BP untuk semua, JNA+NA satu kelas, TMJK+TUJK satu kelas.
            $pintas = [
                ['Semua skema', 'Basic Programming untuk keenam skema', ['BP', 'JNA', 'JWP', 'NA', 'TMJK', 'TUJK']],
                ['JNA + NA', 'Jaringan dasar, satu kelas', ['JNA', 'NA']],
                ['TMJK + TUJK', 'Teknisi jaringan dasar, satu kelas', ['TMJK', 'TUJK']],
                ['JWP saja', 'Lanjutan khusus JWP', ['JWP']],
                ['NA saja', 'Lanjutan khusus NA', ['NA']],
                ['TUJK saja', 'Lanjutan khusus TUJK', ['TUJK']],
            ];

            $kelompok = $daftarBatch->groupBy(fn ($b) => $b->periode.'|'.$b->mode)->sortKeys();
            $pertama = $daftarBatch->firstWhere('id_batch', $terpilih[0] ?? null);
            $kunciAwal = $pertama
                ? $pertama->periode.'|'.$pertama->mode
                : ($kelompok->keys()->first(fn ($k) => str_starts_with($k, substr($tanggalBawaan, 0, 7))) ?? $kelompok->keys()->last());
        @endphp
        <fieldset x-data="{
                      siklus: @js($kunciAwal),
                      n: {{ count($terpilih) }},
                      manual: false,
                      semuaInput() { return this.$root.querySelectorAll('input[type=checkbox]') },
                      grup() { return [...this.$root.querySelectorAll('[data-grup]')].find(g => g.dataset.grup === this.siklus) },
                      kodeTerpilih() { return [...this.$root.querySelectorAll('input[type=checkbox]:checked')].map(i => i.dataset.kode) },
                      hitung() { this.n = this.kodeTerpilih().length; this.$dispatch('batch-berubah') },
                      ganti(kunci) { this.semuaInput().forEach(i => i.checked = false); this.siklus = kunci; this.hitung() },
                      pilih(kode) {
                          this.semuaInput().forEach(i => i.checked = false);
                          this.grup().querySelectorAll('input').forEach(i => i.checked = kode.includes(i.dataset.kode));
                          this.hitung();
                      },
                      aktif(kode) { this.n; const k = this.kodeTerpilih(); return k.length === kode.length && kode.every(x => k.includes(x)) },
                  }"
                  @change="hitung()">
            <legend class="text-lg font-bold text-navy">Pilih batch</legend>
            <p class="mt-1 text-sm text-mute">Batch yang ikut dalam sesi ini.</p>

            <p class="mt-4 text-sm font-medium">Bulan</p>
            <div class="mt-1.5 flex flex-wrap gap-2">
                @foreach ($kelompok as $kunci => $isi)
                    <button type="button" @click="ganti(@js($kunci))"
                            :class="siklus === @js($kunci) ? 'border-cobalt bg-cobalt text-white' : 'border-line bg-white text-navy hover:border-cobalt'"
                            class="h-10 rounded-md border px-4 text-sm font-semibold transition-colors">
                        {{ \Carbon\Carbon::createFromFormat('!Y-m', $isi->first()->periode)->translatedFormat('F Y') }} · {{ $isi->first()->mode }}
                    </button>
                @endforeach
            </div>

            <p class="mt-5 text-sm font-medium">Yang ikut</p>
            <div class="mt-1.5 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pintas as [$nama, $keterangan, $kode])
                    <button type="button" @click="pilih(@js($kode))"
                            :class="aktif(@js($kode)) ? 'border-cobalt bg-sky' : 'border-line bg-white hover:border-cobalt'"
                            class="rounded-md border px-4 py-3 text-left transition-colors">
                        <span class="block text-sm font-semibold text-navy">{{ $nama }}</span>
                        <span class="block text-xs text-mute">{{ $keterangan }}</span>
                    </button>
                @endforeach
            </div>

            <p class="mt-4 text-sm">
                <span class="text-mute">Dipilih:</span>
                <strong x-text="n ? kodeTerpilih().join(' + ') + (n > 1 ? ' (sesi bersama)' : ' (satu skema)') : 'belum ada'"></strong>
            </p>

            <button type="button" @click="manual = ! manual" class="mt-3 text-sm font-semibold text-cobalt hover:underline"
                    x-text="manual ? 'Sembunyikan pilihan manual' : 'Pilih sendiri'"></button>

            @foreach ($kelompok as $kunci => $isi)
                <div x-show="manual && siklus === @js($kunci)" x-cloak data-grup="{{ $kunci }}" class="mt-3 grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                    @foreach ($isi as $b)
                        <label title="{{ $b->nama }}" class="flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-line px-3 py-1.5 text-sm leading-snug has-[:checked]:border-cobalt has-[:checked]:bg-sky">
                            <input type="checkbox" name="id_batch[]" value="{{ $b->id_batch }}" data-kode="{{ $b->skema->nama_skema }}" data-mode="{{ $b->mode }}"
                                   @checked(in_array($b->id_batch, $terpilih, true))
                                   class="rounded border-line text-cobalt focus:ring-cobalt">
                            <span>{{ $b->skema->nama_lengkap }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
            @error('id_batch.*') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
        </fieldset>
