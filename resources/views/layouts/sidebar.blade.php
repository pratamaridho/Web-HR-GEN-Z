@php
    $pengguna = auth()->user();

    // Menu mengikuti urutan kerja: master data, peserta, jadwal, pelaksanaan.
    // Item hanya tampil bila rutenya ada dan Gate-nya lolos.
    $kelompok = [
        ['', [
            ['dashboard', 'Dashboard', 'view-reports'],
        ]],
        ['Master data', [
            ['data-master', 'Program Master', 'manage-operations', ['tab' => 'skema']],
            ['data-master', 'Batch Management', 'manage-operations', ['tab' => 'batch']],
            ['data-master', 'Trainer Management', 'manage-operations', ['tab' => 'trainer']],
        ]],
        ['Peserta', [
            ['peserta', 'Participant Mapping', 'manage-operations'],
        ]],
        ['Jadwal', [
            ['jadwal-sesi', 'Training Schedule', 'manage-operations'],
            ['master-schedule', 'Master Schedule', 'manage-operations'],
            ['validasi-durasi', 'Validasi durasi', 'manage-operations'],
        ]],
        ['Pelaksanaan', [
            ['rekap-kehadiran', 'Attendance', 'manage-operations'],
            ['jadwal-saya', 'Jadwal saya', 'trainer'],
        ]],
    ];

    $kelompok = collect($kelompok)
        ->map(fn ($g) => [$g[0], collect($g[1])->filter(
            fn ($i) => \Illuminate\Support\Facades\Route::has($i[0]) && \Illuminate\Support\Facades\Gate::allows($i[2])
        )])
        ->filter(fn ($g) => $g[1]->isNotEmpty());

    $labelPeran = $pengguna->trainer?->nama ?? $pengguna->peran()?->labelPanjang() ?? '';
@endphp

            {{-- Sidebar --}}
            <aside id="side"
                   :class="buka ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 flex w-60 -translate-x-full flex-col bg-navy text-white transition-transform md:translate-x-0">
                <div class="px-5 py-5">
                    <p class="text-lg font-bold tracking-tight">HRGZ</p>
                    <p class="text-xs text-white/60">Penjadwalan pelatihan</p>
                </div>

                <nav class="flex-1 overflow-y-auto pb-4" aria-label="Menu utama">
                    @foreach ($kelompok as [$judul, $item])
                        <div class="mt-4 first:mt-0">
                            @if ($judul !== '')
                                <p class="px-5 pb-1 text-[11px] font-semibold uppercase tracking-wide text-white/50">{{ $judul }}</p>
                            @endif
                            @foreach ($item as $i)
                                @php
                                    $param = $i[3] ?? [];
                                    $aktif = request()->routeIs($i[0])
                                        && (! isset($param['tab']) || request('tab', 'batch') === $param['tab']);
                                @endphp
                                <a href="{{ route($i[0], $param) }}" data-menu="{{ $i[0] }}"
                                   @if ($aktif) aria-current="page" @endif
                                   class="block border-l-2 py-2 pl-[18px] pr-3 text-sm {{ $aktif ? 'border-white bg-white/10 font-semibold text-white' : 'border-transparent text-white/75 hover:bg-white/5 hover:text-white' }}">
                                    {{ $i[1] }}
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>

                <div class="border-t border-white/15 px-5 py-4">
                    <p class="text-sm font-semibold">{{ $pengguna->name }}</p>
                    <p class="text-xs text-white/60">{{ $labelPeran }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mt-3 h-10 w-full rounded-md border border-white/30 text-sm font-semibold text-white hover:bg-white/10">Keluar</button>
                    </form>
                </div>
            </aside>
