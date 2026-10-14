        {{-- Batch (FR 04) --}}
        @php
            $cara = old('cara', 'otomatis');
            $awalBatch = $errors->has('id_skema') ? 3 : ($errors->any() ? 2 : 1);
        @endphp
        <section x-show="tab === 'batch'" x-cloak class="mt-4">
            <div class="flex justify-end">
                <button type="button" @click="form = !form"
                        class="inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">
                    Tambah batch
                </button>
            </div>

            <x-form-langkah x-show="form" x-cloak :action="route('data-master.batch.bulanan')" title="Tambah batch"
                            :data="'cara: \''.$cara.'\''" :awal="$awalBatch" batal="form = false"
                            x-bind:action="cara === 'otomatis' ? '{{ route('data-master.batch.bulanan') }}' : '{{ route('data-master.batch.simpan') }}'"
                            simpan="Simpan batch" simpan-js="cara === 'otomatis' ? 'Buat 6 batch' : 'Simpan batch'">
                @if ($errBatch)
                    <div role="alert" class="mb-4 rounded-md bg-badbg p-3 text-sm text-bad">{{ $errBatch }}</div>
                @endif
                @error('periode') <p class="mb-4 rounded-md bg-badbg p-3 text-sm text-bad">{{ $message }}</p> @enderror

                <div data-langkah data-judul="Pilih cara">
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-line p-4 has-[:checked]:border-cobalt has-[:checked]:bg-sky">
                            <input type="radio" name="cara" value="otomatis" x-model="cara" class="mt-1 border-line text-cobalt focus:ring-cobalt">
                            <span>
                                <span class="block font-semibold text-navy">Otomatis, enam batch</span>
                                <span class="block text-sm text-mute">Satu batch untuk tiap skema. Yang sudah ada dilewati.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-line p-4 has-[:checked]:border-cobalt has-[:checked]:bg-sky">
                            <input type="radio" name="cara" value="satu" x-model="cara" class="mt-1 border-line text-cobalt focus:ring-cobalt">
                            <span>
                                <span class="block font-semibold text-navy">Satu batch saja</span>
                                <span class="block text-sm text-mute">Pilih skemanya sendiri.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div data-langkah data-judul="Pilih bulan dan mode">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium">Bulan</span>
                            <input type="month" name="periode" value="{{ old('periode', $bulanBerikutnya) }}" required data-pesan="Pilih bulannya dulu."
                                   class="{{ $kelasField }} {{ $errors->has('periode') ? 'border-bad' : 'border-line' }}">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium">Mode</span>
                            <select name="mode" class="{{ $kelasField }} {{ $errors->has('mode') ? 'border-bad' : 'border-line' }}">
                                @foreach (['Online', 'Offline'] as $m)
                                    <option value="{{ $m }}" @selected(old('mode', 'Online') === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                            @error('mode') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                        </label>
                    </div>
                    <p class="mt-3 text-sm text-mute" x-show="cara === 'otomatis'">Enam batch akan dibuat, satu untuk tiap skema.</p>
                </div>

                <div data-langkah data-judul="Pilih skema" data-jika="cara === 'satu'">
                    <label class="block max-w-sm">
                        <span class="mb-1.5 block text-sm font-medium">Skema</span>
                        <select name="id_skema" required data-pesan="Pilih skemanya dulu."
                                class="{{ $kelasField }} {{ $errors->has('id_skema') ? 'border-bad' : 'border-line' }}">
                            <option value="">Pilih skema</option>
                            @foreach ($skema as $s)
                                <option value="{{ $s->id_skema }}" @selected((string) old('id_skema') === (string) $s->id_skema)>{{ $s->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        @error('id_skema') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                    </label>
                </div>
            </x-form-langkah>

            @include('data-master.tabel-batch')
        </section>
