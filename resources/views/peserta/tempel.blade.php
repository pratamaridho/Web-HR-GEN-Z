        {{-- Tempel banyak peserta --}}
        <x-form-langkah x-show="tempel" x-cloak :action="route('peserta.impor')" title="Tempel banyak"
                        :judul="['Tempel daftar', 'Pilih batch']" simpan="Simpan semua" batal="tempel = false"
                        :awal="$errors->has('tempel') ? 1 : 1">
            @error('tempel')
                <div role="alert" class="mb-4 whitespace-pre-line rounded-md bg-badbg p-3 text-sm text-bad">{{ $message }}</div>
            @enderror

            <div data-langkah>
                <p class="text-sm text-mute">Satu peserta per baris: <code class="rounded bg-mist px-1">Nama, email</code>. Bisa disalin dari spreadsheet.</p>
                <textarea name="tempel" rows="6" required data-pesan="Tempel daftar peserta dulu."
                          placeholder="Ayu Lestari, ayu.lestari@example.test&#10;Budi Hartono, budi.hartono@example.test"
                          class="mt-3 w-full rounded-md border border-line bg-white p-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25">{{ old('tempel') }}</textarea>
            </div>

            <div data-langkah>
                <label class="block max-w-sm">
                    <span class="mb-1.5 block text-sm font-medium">Batch</span>
                    <select name="id_batch" class="{{ $kelas }}">
                        <option value="">Pilih nanti</option>
                        @foreach ($daftarBatch as $b)
                            <option value="{{ $b->id_batch }}">{{ $labelBatch($b) }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="mt-2 text-sm text-mute">Peserta baru menunggu validasi. Tanpa batch, ia belum ikut sesi mana pun.</p>
            </div>
        </x-form-langkah>
