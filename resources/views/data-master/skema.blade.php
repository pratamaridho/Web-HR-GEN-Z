        {{-- Skema (FR 02) --}}
        <section x-show="tab === 'skema'" class="mt-4 overflow-x-auto rounded-md border border-line bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-sky text-navy"><tr>
                    <th class="px-4 py-3 font-semibold">Skema</th>
                    <th class="px-4 py-3 font-semibold">Nama lengkap</th>
                    <th class="px-4 py-3 font-semibold">Online (hari)</th>
                    <th class="px-4 py-3 font-semibold">Offline (hari)</th>
                </tr></thead>
                <tbody>
                    @forelse ($skema as $s)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-medium">{{ $s->nama_skema }}</td>
                            <td class="px-4 py-3">{{ $s->nama_lengkap }}</td>
                            <td class="px-4 py-3">{{ $hari($s->durasi_online) }}</td>
                            <td class="px-4 py-3">{{ $hari($s->durasi_offline) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-mute">Belum ada skema. Muat data awal lewat seeder (lihat README).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
