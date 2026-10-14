        {{-- Trainer (FR 03) --}}
        <section x-show="tab === 'trainer'" x-cloak class="mt-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-mute">Kompetensi: skema yang dikuasai trainer.</p>
                <button type="button" @click="formTrainer = !formTrainer"
                        class="inline-flex h-10 items-center rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">
                    Tambah trainer
                </button>
            </div>

            @include('data-master.trainer-form')

            <div class="mt-4 overflow-x-auto rounded-md border border-line bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sky text-navy"><tr>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Kompetensi</th>
                        <th class="px-4 py-3 font-semibold">Sesi</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($trainer as $t)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium">{{ $t->nama }}</td>
                                <td class="px-4 py-3">{{ $t->kompetensi }}</td>
                                <td class="px-4 py-3">{{ $t->sesi_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-mute">Belum ada trainer. Klik "Tambah trainer".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
