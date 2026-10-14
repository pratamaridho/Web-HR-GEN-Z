        {{-- Penggabungan skema --}}
        <section class="mt-4 rounded-md border border-line bg-white p-5">
            <h2 class="text-base font-bold">Penggabungan skema</h2>
            @foreach ($integrasi as $periode => $cek)
                <p class="mt-3 text-xs font-semibold text-mute">{{ \Carbon\Carbon::createFromFormat('!Y-m', $periode)->translatedFormat('F Y') }}</p>
                <ul class="mt-1 space-y-1.5 text-sm">
                    @foreach ($cek as $c)
                        <li class="flex flex-wrap items-center gap-2">
                            <x-badge>{{ $c['ok'] === null ? 'Menunggu' : ($c['ok'] ? 'Sesuai' : 'Belum') }}</x-badge>
                            <span class="font-medium">{{ $c['aturan'] }}</span>
                            <span class="text-mute">{{ $c['pesan'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </section>
