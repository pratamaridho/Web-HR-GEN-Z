@php
    $ubah = $sesi !== null;
    $kelas = 'h-10 w-full rounded-md border bg-white px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25';
    $batasi = fn ($f) => $errors->has($f) ? 'border-bad bg-badbg/40' : 'border-line';
    $nilai = fn ($f, $asal = null) => old($f, $ubah ? $asal : null);
    $terpilih = array_map('intval', (array) old('id_batch', $idBatchTerpilih));

    // Langkah yang dibuka: yang pertama bermasalah bila server menolak.
    $adaBatch = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'id_batch'));
    $adaWaktu = $errors->hasAny(['tanggal', 'jam_mulai', 'jam_selesai']);
    $awal = $adaBatch ? 1 : ($adaWaktu ? 2 : ($errors->any() ? 3 : 1));
    $pesanServer = array_filter([
        $errors->first('id_batch'), $errors->first('tanggal'), $errors->first('id_trainer'), $errors->first('room'),
    ]);
@endphp

<x-app-layout :title="$ubah ? 'Ubah sesi' : 'Tambah sesi'">
    <h1 class="text-2xl font-bold text-navy">{{ $ubah ? 'Ubah sesi' : 'Tambah sesi' }}</h1>

    @if ($pesanServer)
        <div role="alert" class="mt-4 rounded-md bg-badbg p-4 text-sm text-bad">
            <p class="font-semibold">Sesi tidak disimpan</p>
            @foreach ($pesanServer as $pesan)
                <p class="mt-1">{{ $pesan }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $ubah ? route('jadwal-sesi.ubah', $sesi) : route('jadwal-sesi.simpan') }}"
          class="mt-6 rounded-md border border-line bg-white p-5" novalidate
          x-data="{
              langkah: {{ $awal }},
              semua: {{ $ubah ? 'true' : 'false' }},
              jamDiubah: {{ ($ubah || old('jam_mulai')) ? 'true' : 'false' }},
              pesan: '',
              judul: ['Pilih batch', 'Atur jadwal', 'Isi detail sesi'],
              arah: 'maju',
              nilai(n) { return this.$root.querySelector('[name=' + n + ']').value },
              dipilih() { return this.$root.querySelectorAll('input[name=\'id_batch[]\']:checked') },
              isiJam() {
                  const pertama = this.dipilih()[0];
                  if (this.jamDiubah || ! pertama) return;
                  const online = pertama.dataset.mode === 'Online';
                  this.$root.querySelector('[name=jam_mulai]').value = online ? '19:00' : '08:30';
                  this.$root.querySelector('[name=jam_selesai]').value = online ? '21:30' : '15:30';
              },
              lanjut() {
                  this.pesan = '';
                  if (this.langkah === 1 && this.dipilih().length === 0) { this.pesan = 'Pilih minimal satu batch.'; return; }
                  if (this.langkah === 2) {
                      if (! this.nilai('tanggal')) { this.pesan = 'Isi tanggal sesi.'; return; }
                      if (! this.nilai('jam_mulai') || ! this.nilai('jam_selesai')) { this.pesan = 'Isi jam mulai dan jam selesai.'; return; }
                      if (this.nilai('jam_selesai') <= this.nilai('jam_mulai')) { this.pesan = 'Jam selesai harus setelah jam mulai.'; return; }
                  }
                  this.arah = 'maju'; this.langkah++;
              },
          }"
          @batch-berubah="isiJam()">
        @csrf
        @if ($ubah) @method('PUT') @endif

        @unless ($ubah)
            <div class="mb-6">
                <p class="text-sm font-semibold text-navy" x-text="'Langkah ' + langkah + ' dari 3'"></p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-sky">
                    <div class="h-full rounded-full bg-cobalt transition-all duration-300 ease-out"
                         :style="'width:' + (langkah / 3 * 100) + '%'"></div>
                </div>
            </div>
        @endunless

        <div x-show="semua || langkah === 1" :class="arah === 'maju' ? 'masuk-maju' : 'masuk-mundur'">
            @include('form-sesi.batch')
        </div>

        <div x-show="semua || langkah === 2" x-cloak :class="arah === 'maju' ? 'masuk-maju' : 'masuk-mundur'">
            @include('form-sesi.waktu')
        </div>

        <div x-show="semua || langkah === 3" x-cloak :class="arah === 'maju' ? 'masuk-maju' : 'masuk-mundur'">
            @include('form-sesi.detail')
        </div>

        <p x-show="pesan" x-text="pesan" x-cloak role="alert" class="mt-4 rounded-md bg-badbg p-3 text-sm text-bad"></p>

        <div class="mt-6 flex flex-wrap gap-2">
            @unless ($ubah)
                <button type="button" x-show="langkah > 1" x-cloak @click="pesan = ''; arah = 'mundur'; langkah--"
                        class="h-10 rounded-md border border-line px-4 text-sm font-semibold text-navy">Kembali</button>
                <button type="button" x-show="langkah < 3" @click="lanjut()"
                        class="group h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white transition-colors hover:bg-cobalt-dark active:scale-95">Lanjut <span aria-hidden="true" class="inline-block transition-transform group-hover:translate-x-0.5">&rarr;</span></button>
            @endunless
            <button type="submit" x-show="semua || langkah === 3" @if (! $ubah) x-cloak @endif
                    class="h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Simpan sesi</button>
            <a href="{{ route('jadwal-sesi') }}" class="inline-flex h-10 items-center rounded-md border border-line px-4 text-sm font-semibold text-navy">Batal</a>
        </div>
    </form>
</x-app-layout>
