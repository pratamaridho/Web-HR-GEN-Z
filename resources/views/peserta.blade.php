@php
    $kelas = 'h-10 w-full rounded-md border border-line bg-white px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25';
    $bukaTambah = $errors->has('nama') || $errors->has('email') || $errors->has('id_batch');
    $bukaDaftar = $errors->has('daftar') || $errors->has('id_peserta') || $errors->has('id_batch');
    $labelBatch = fn ($b) => $b->nama;
@endphp

<x-app-layout title="Participant Mapping">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-navy">Participant Mapping</h1>
            <p class="mt-1 text-sm text-mute">Peserta harus masuk batch agar ikut sesi. Satu peserta satu batch per bulan.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div role="status" class="mt-4 rounded-md bg-okbg p-3 text-sm text-ok">{{ session('sukses') }}</div>
    @endif

    <div x-data="{ tambah: {{ $bukaTambah ? 'true' : 'false' }}, daftar: {{ $bukaDaftar ? 'true' : 'false' }}, tempel: {{ $errors->has('tempel') ? 'true' : 'false' }} }">
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" @click="tambah = !tambah"
                    class="h-10 rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">Tambah peserta</button>
            <button type="button" @click="tempel = !tempel"
                    class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy">Tempel banyak</button>
            <button type="button" @click="daftar = !daftar"
                    class="h-10 rounded-md border border-line bg-white px-4 text-sm font-semibold text-navy">Masukkan ke batch</button>
        </div>

        @include('peserta.tambah')

        @include('peserta.tempel')

        @include('peserta.daftar')

    </div>

        @include('peserta.tabel')

</x-app-layout>
