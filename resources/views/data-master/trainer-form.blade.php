            <x-form-langkah x-show="formTrainer" x-cloak :action="route('data-master.trainer.simpan')" title="Tambah trainer"
                            :judul="['Nama trainer', 'Kompetensi']" simpan="Simpan trainer" batal="formTrainer = false"
                            :awal="$errors->has('nama') ? 1 : ($errors->has('kompetensi') ? 2 : 1)">
                <div data-langkah>
                    <label class="block max-w-sm">
                        <span class="mb-1.5 block text-sm font-medium">Nama</span>
                        <input type="text" name="nama" value="{{ old('nama') }}" maxlength="50" required data-pesan="Isi nama trainer dulu."
                               class="{{ $kelasField }} {{ $errors->has('nama') ? 'border-bad' : 'border-line' }}">
                        @error('nama') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                    </label>
                </div>
                <div data-langkah>
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium">Kompetensi</span>
                        <input type="text" name="kompetensi" value="{{ old('kompetensi') }}" maxlength="100"
                               placeholder="Contoh: Basic Programming dan Junior Web Programmer"
                               class="{{ $kelasField }} {{ $errors->has('kompetensi') ? 'border-bad' : 'border-line' }}">
                        @error('kompetensi') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                    </label>
                </div>
            </x-form-langkah>
