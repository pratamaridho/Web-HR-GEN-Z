            <h2 class="text-lg font-bold text-navy">Isi detail sesi</h2>
            <p class="mb-4 mt-1 text-sm text-mute">Nama, pengajar, dan tempat sesi.</p>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium">Nama sesi</span>
                    <input type="text" name="nama_sesi" value="{{ $nilai('nama_sesi', $sesi?->nama_sesi) }}" class="{{ $kelas }} {{ $batasi('nama_sesi') }}">
                    @error('nama_sesi') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">Trainer</span>
                    <select name="id_trainer" class="{{ $kelas }} {{ $batasi('id_trainer') }}">
                        <option value="">Pilih trainer</option>
                        @foreach ($daftarTrainer as $t)
                            <option value="{{ $t->id_trainer }}" @selected((string) $nilai('id_trainer', $sesi?->id_trainer) === (string) $t->id_trainer)>{{ $t->nama }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">Room</span>
                    <select name="room" class="{{ $kelas }} {{ $batasi('room') }}">
                        <option value="">Pilih room</option>
                        @foreach ($daftarRoom as $r)
                            <option value="{{ $r }}" @selected($nilai('room', $sesi?->room) === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($ubah && in_array($sesi->status, $daftarStatus, true))
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium">Status</span>
                        <select name="status" class="{{ $kelas }} {{ $batasi('status') }}">
                            @foreach ($daftarStatus as $st)
                                <option value="{{ $st }}" @selected(old('status', $sesi?->status ?? 'Planned') === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">Catatan <span class="font-normal text-mute">(opsional)</span></span>
                    <input type="text" name="catatan" maxlength="255" value="{{ old('catatan', $sesi?->catatan) }}"
                           placeholder="Misalnya: setelah Basic Programming selesai"
                           class="{{ $kelas }} {{ $batasi('catatan') }}">
                    @error('catatan') <p class="mt-1.5 text-sm text-bad">{{ $message }}</p> @enderror
                </label>
            </div>
            <p class="mt-3 text-sm text-mute">Durasi dihitung otomatis: online 1 hari, offline sampai 4 jam setengah hari. Status mengikuti tanggal dan jam.</p>
