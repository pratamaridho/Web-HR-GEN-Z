{{--
    Formulir bertahap (Lanjut / Kembali) seperti Tambah sesi.
    Tiap langkah ditulis sebagai <div data-langkah> di dalam slot:
    - data-judul="..." judul langkah (atau pakai prop judul)
    - data-jika="ekspresi" langkah hanya ada bila ekspresi benar (memakai data)
    - data-butuh-centang="pesan" butuh minimal satu kotak dicentang
    Isian dengan atribut required harus terisi sebelum lanjut (pesan dari data-pesan).
    Prop data berisi status Alpine tambahan, misalnya "cara: 'otomatis'".
--}}
@props(['action', 'judul' => [], 'title', 'simpan' => 'Simpan', 'awal' => 1, 'batal' => null, 'ket' => null, 'data' => null, 'simpanJs' => null])

<form method="POST" action="{{ $action }}" novalidate
      {{ $attributes->merge(['class' => 'mt-4 rounded-md border border-line bg-white p-5']) }}
      x-data="{
          {!! $data ? $data.',' : '' !!}
          langkah: {{ (int) $awal }},
          pesan: '',
          arah: 'maju',
          judul: @js($judul),
          semua() { return [...this.$root.querySelectorAll('[data-langkah]')] },
          aktif() { return this.semua().filter(e => ! e.dataset.jika || Alpine.evaluate(this.$root, e.dataset.jika)) },
          get total() { return Math.max(this.aktif().length, 1) },
          get namaLangkah() { const e = this.aktif()[this.langkah - 1]; return (e && e.dataset.judul) || this.judul[this.langkah - 1] || '' },
          cek() {
              const el = this.aktif()[this.langkah - 1];
              if (! el) return true;
              const kosong = [...el.querySelectorAll('[required]')].find(e => ! String(e.value).trim());
              if (kosong) { this.pesan = kosong.dataset.pesan || 'Lengkapi isian ini dulu.'; kosong.focus(); return false; }
              if (el.dataset.butuhCentang !== undefined && ! el.querySelector('input[type=checkbox]:checked')) {
                  this.pesan = el.dataset.butuhCentang || 'Pilih minimal satu.'; return false;
              }
              this.pesan = ''; return true;
          },
          lanjut() { if (this.cek()) { this.arah = 'maju'; this.langkah++ } },
          kembali() { this.pesan = ''; this.arah = 'mundur'; this.langkah-- },
      }"
      x-effect="const a = aktif(); semua().forEach(e => e.hidden = a[langkah - 1] !== e)"
      @submit="if (! cek()) $event.preventDefault()"
      @keydown.enter="if ($event.target.tagName !== 'TEXTAREA' && langkah < total) { $event.preventDefault(); lanjut() }">
    @csrf

    <h2 class="text-lg font-bold">{{ $title }}</h2>
    @if ($ket) <p class="mt-1 text-sm text-mute">{{ $ket }}</p> @endif

    <div class="mt-4" x-show="total > 1">
        <p class="text-sm font-semibold text-navy" x-text="'Langkah ' + langkah + ' dari ' + total + ' · ' + namaLangkah"></p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-sky">
            <div class="h-full rounded-full bg-cobalt transition-all duration-300 ease-out" :style="'width:' + (langkah / total * 100) + '%'"></div>
        </div>
    </div>

    <div class="mt-4" :class="arah === 'maju' ? 'masuk-maju' : 'masuk-mundur'">
        {{ $slot }}
    </div>

    <p x-show="pesan" x-text="pesan" x-cloak role="alert" class="mt-4 rounded-md bg-badbg p-3 text-sm text-bad"></p>

    <div class="mt-5 flex flex-wrap gap-2">
        <button type="button" x-show="langkah > 1" x-cloak @click="kembali()"
                class="h-10 rounded-md border border-line px-4 text-sm font-semibold text-navy">Kembali</button>
        <button type="button" x-show="langkah < total" @click="lanjut()"
                class="group h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white transition-colors hover:bg-cobalt-dark active:scale-95">Lanjut <span aria-hidden="true" class="inline-block transition-transform group-hover:translate-x-0.5">&rarr;</span></button>
        <button type="submit" x-show="langkah >= total" x-cloak
                @if ($simpanJs) x-text="{{ $simpanJs }}" @endif
                class="h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">{{ $simpan }}</button>
        @if ($batal)
            <button type="button" @click="{{ $batal }}" class="h-10 rounded-md border border-line px-4 text-sm font-semibold text-navy">Batal</button>
        @endif
    </div>
</form>
