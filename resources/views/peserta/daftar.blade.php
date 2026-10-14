        {{-- Masukkan ke batch (FR 06) --}}
        <x-form-langkah x-show="daftar" x-cloak :action="route('peserta.daftar')" title="Masukkan ke batch"
                        :judul="['Pilih peserta', 'Pilih batch']" simpan="Simpan" batal="daftar = false"
                        :awal="$errors->has('id_batch') ? 2 : 1">
            @error('daftar')
                <div role="alert" class="mb-4 whitespace-pre-line rounded-md bg-badbg p-3 text-sm text-bad">{{ $message }}</div>
            @enderror

            <div data-langkah data-butuh-centang="Pilih minimal satu peserta.">
                <div x-data="{ cari: '' }" class="max-w-xl">
                    <div class="flex gap-2">
                        <input type="search" x-model="cari" placeholder="Cari nama atau email" class="{{ $kelas }}">
                        <button type="button" class="h-10 shrink-0 rounded-md border border-line px-3 text-sm font-semibold text-navy"
                                @click="$root.querySelectorAll('label[data-t]').forEach(l => { if (l.style.display !== 'none') l.querySelector('input').checked = true })">Pilih yang tampil</button>
                    </div>
                    <div class="mt-2 max-h-56 space-y-1 overflow-y-auto rounded-md border border-line p-2">
                        @foreach ($semuaPeserta as $p)
                            <label data-t="{{ strtolower($p->nama.' '.$p->email) }}"
                                   x-show="cari === '' || $el.dataset.t.includes(cari.toLowerCase())"
                                   class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-mist">
                                <input type="checkbox" name="id_peserta[]" value="{{ $p->id_peserta }}"
                                       @checked(in_array($p->id_peserta, array_map('intval', (array) old('id_peserta', [])), true))
                                       class="rounded border-line text-cobalt focus:ring-cobalt">
                                <span>{{ $p->nama }} <span class="text-mute">· {{ $p->email }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('id_peserta') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </div>
            </div>

            <div data-langkah>
                <label class="block max-w-sm">
                    <span class="mb-1.5 block text-sm font-medium">Batch</span>
                    <select name="id_batch" required data-pesan="Pilih batchnya dulu." class="{{ $kelas }}">
                        <option value="">Pilih batch</option>
                        @foreach ($daftarBatch as $b)
                            <option value="{{ $b->id_batch }}" @selected((string) old('id_batch') === (string) $b->id_batch)>{{ $labelBatch($b) }}</option>
                        @endforeach
                    </select>
                    @error('id_batch') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
            </div>
        </x-form-langkah>
