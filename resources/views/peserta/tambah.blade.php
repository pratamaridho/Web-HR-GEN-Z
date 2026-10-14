        {{-- Tambah peserta (FR 05) --}}
        <x-form-langkah x-show="tambah" x-cloak :action="route('peserta.simpan')" title="Tambah peserta"
                        :judul="['Nama', 'Email', 'Batch']" simpan="Simpan" batal="tambah = false"
                        :awal="$errors->has('email') ? 2 : ($errors->has('id_batch') ? 3 : 1)">
            @error('email')
                <div role="alert" class="mb-4 rounded-md bg-badbg p-3 text-sm text-bad">{{ $message }}</div>
            @enderror

            <div data-langkah>
                <label class="block max-w-sm">
                    <span class="mb-1.5 block text-sm font-medium">Nama</span>
                    <input type="text" name="nama" value="{{ old('nama') }}" required data-pesan="Isi nama peserta dulu." class="{{ $kelas }}">
                    @error('nama') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
            </div>

            <div data-langkah>
                <label class="block max-w-sm">
                    <span class="mb-1.5 block text-sm font-medium">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required data-pesan="Isi email peserta dulu." class="{{ $kelas }}">
                </label>
            </div>

            <div data-langkah>
                <label class="block max-w-sm">
                    <span class="mb-1.5 block text-sm font-medium">Batch</span>
                    <select name="id_batch" class="{{ $kelas }}">
                        <option value="">Pilih nanti</option>
                        @foreach ($daftarBatch as $b)
                            <option value="{{ $b->id_batch }}" @selected((string) old('id_batch') === (string) $b->id_batch)>{{ $labelBatch($b) }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="mt-2 text-sm text-mute">Peserta baru menunggu validasi. Tanpa batch, ia belum ikut sesi mana pun.</p>
            </div>
        </x-form-langkah>
