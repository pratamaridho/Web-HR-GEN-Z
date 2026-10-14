@props(['label' => null])

@php
    $teks = $label ?? trim((string) $slot);
    $warna = match ($teks) {
        'Completed', 'OK', 'Sesuai', 'Hadir' => 'bg-okbg text-ok',
        'Ongoing', 'Izin', 'Belum', 'Menunggu' => 'bg-warnbg text-warn',
        'Cancelled', 'Dropped', 'Alpa', 'Tidak sesuai', 'Kurang', 'Lebih' => 'bg-badbg text-bad',
        'Common' => 'bg-sky text-cobalt',
        default => 'bg-mist text-mute ring-1 ring-line', // Planned, Registered, Exclusive, dll.
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold', $warna]) }}>{{ $teks }}</span>
