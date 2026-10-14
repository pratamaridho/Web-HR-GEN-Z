    {{-- Rincian sesi yang diklik pada kalender --}}
    <div x-show="buka" x-cloak
         class="fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Rincian sesi">
        <div class="absolute inset-0 bg-ink/50" @click="buka = false"></div>

        <div class="muncul relative w-full max-w-md rounded-md bg-white p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-navy" x-text="sesi.nama"></h3>
                    <p class="mt-0.5 text-sm text-mute" x-text="sesi.tanggal + ' · ' + sesi.jam"></p>
                </div>
                <button type="button" @click="buka = false" aria-label="Tutup"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-md border border-line text-navy">&times;</button>
            </div>

            <dl class="mt-4 grid grid-cols-[6rem_1fr] gap-x-3 gap-y-2 text-sm">
                <dt class="text-mute">Skema</dt><dd x-text="sesi.skema.join(', ')"></dd>
                <dt class="text-mute">Peserta</dt><dd x-text="sesi.peserta + ' peserta'"></dd>
                <dt class="text-mute">Jenis</dt><dd x-text="sesi.jenis + (sesi.paralel ? ' · Parallel' : '')"></dd>
                <dt class="text-mute">Trainer</dt><dd x-text="sesi.trainer"></dd>
                <dt class="text-mute">Room</dt><dd x-text="sesi.room"></dd>
                <dt class="text-mute">Status</dt><dd x-text="sesi.status"></dd>
                <dt class="text-mute" x-show="sesi.catatan">Catatan</dt><dd x-show="sesi.catatan" x-text="sesi.catatan"></dd>
            </dl>

            <a :href="sesi.ubah"
               class="mt-5 inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Ubah sesi</a>
        </div>
    </div>
