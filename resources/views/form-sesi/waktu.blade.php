            <h2 class="text-lg font-bold text-navy">Atur jadwal</h2>
            <p class="mb-4 mt-1 text-sm text-mute">Tanggal dan jam sesi.</p>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2 md:w-1/2">
                    <span class="mb-1.5 block text-sm font-medium">Tanggal</span>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $sesi?->tanggal?->toDateString() ?? $tanggalBawaan) }}" class="{{ $kelas }} {{ $batasi('tanggal') }}">
                    @error('tanggal') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">Jam mulai</span>
                    <input type="time" name="jam_mulai" value="{{ old('jam_mulai', $sesi ? substr($sesi->jam_mulai, 0, 5) : '19:00') }}"
                           @input="jamDiubah = true" class="{{ $kelas }} {{ $batasi('jam_mulai') }}">
                    @error('jam_mulai') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">Jam selesai</span>
                    <input type="time" name="jam_selesai" value="{{ old('jam_selesai', $sesi ? substr($sesi->jam_selesai, 0, 5) : '21:30') }}"
                           @input="jamDiubah = true" class="{{ $kelas }} {{ $batasi('jam_selesai') }}">
                    @error('jam_selesai') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
            </div>
            <p class="mt-3 text-sm text-mute">Jam terisi otomatis dari mode batch (online 19.00–21.30, offline 08.30–15.30) dan bisa diubah.</p>
