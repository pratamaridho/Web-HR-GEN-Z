@php
    // 3.0 -> "3", 0.5 -> "0,5" (format desain)
    $hari = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
    $errBatch = $errors->first('batch');
    $errTrainer = $errors->has('nama') || $errors->has('kompetensi');
    $bukaForm = $errors->any() && ! $errTrainer;
    $tabAwal = $errTrainer ? 'trainer' : (in_array(request('tab'), ['skema', 'batch', 'trainer'], true) ? request('tab') : 'batch');
    $kelasField = 'h-10 w-full rounded-md border bg-white px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25';
    $judulModul = ['skema' => 'Program Master', 'batch' => 'Batch Management', 'trainer' => 'Trainer Management'][$tabAwal];
@endphp

<x-app-layout :title="$judulModul">
    <div>
        <h1 class="text-2xl font-bold text-navy">{{ $judulModul }}</h1>
        @if ($tabAwal === 'batch')
            <p class="mt-1 text-sm text-mute">Batch: satu skema, satu bulan, satu mode. Dibuat sekali per bulan.</p>
        @endif
    </div>

    @if (session('sukses'))
        <div role="status" class="mt-4 rounded-md bg-okbg p-3 text-sm text-ok">{{ session('sukses') }}</div>
    @endif

    <div x-data="{ tab: '{{ $tabAwal }}', form: {{ $bukaForm ? 'true' : 'false' }}, formTrainer: {{ $errTrainer ? 'true' : 'false' }} }" class="mt-6">

        @include('data-master.skema')

        @include('data-master.integrasi')

        @include('data-master.batch')

        @include('data-master.trainer')

    </div>
</x-app-layout>
