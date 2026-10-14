        {{-- Verifikasi total durasi tiap skema --}}
        <section class="mt-6 rounded-md border border-line bg-white p-5">
            <h2 class="text-base font-bold">Verifikasi durasi tiap skema</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="text-xs text-mute">
                        <th class="pb-2 font-medium">Skema</th>
                        <th class="pb-2 font-medium">Periode</th>
                        <th class="pb-2 font-medium">Wajib</th>
                        <th class="pb-2 font-medium">Terjadwal</th>
                        <th class="pb-2 font-medium">Status</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($durasi as $d)
                            <tr class="border-t border-line">
                                <td class="py-2 font-medium">{{ $d['skema'] }}</td>
                                <td class="py-2">{{ $d['periode'] }}</td>
                                <td class="py-2">{{ $angka($d['wajib']) }} hari</td>
                                <td class="py-2">{{ $angka($d['terjadwal']) }} hari</td>
                                <td class="py-2"><x-badge>{{ $d['ok'] ? 'Sesuai' : 'Tidak sesuai' }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
