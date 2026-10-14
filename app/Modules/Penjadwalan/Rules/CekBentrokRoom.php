<?php

namespace App\Modules\Penjadwalan\Rules;

use App\Modules\Penjadwalan\Models\Sesi;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CekBentrokRoom implements ValidationRule
{
    public function __construct(
        private readonly string $room,
        private readonly string $tanggal,
        private readonly string $jamMulai,
        private readonly string $jamSelesai,
        private readonly ?int $kecualiIdSesi = null,
    ) {}

    /**
     * Tolak jika room sudah dipakai sesi lain yang jamnya beririsan pada
     * tanggal yang sama. Sesi yang berurutan dan sesi Cancelled tidak dianggap bentrok.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bentrok = Sesi::where('room', $this->room)
            ->whereDate('tanggal', $this->tanggal)
            ->where('jam_mulai', '<', $this->jamSelesai)
            ->where('jam_selesai', '>', $this->jamMulai)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '<>', 'Cancelled'))
            ->when($this->kecualiIdSesi, fn ($q, $id) => $q->where('id_sesi', '!=', $id))
            ->first();

        if ($bentrok === null) {
            return;
        }

        $hari = Carbon::parse($this->tanggal)->translatedFormat('l, j M Y');
        $jam = str_replace(':', '.', substr($bentrok->jam_mulai, 0, 5)).'–'.str_replace(':', '.', substr($bentrok->jam_selesai, 0, 5));

        $fail("Room bentrok. {$this->room} sudah dipakai {$bentrok->nama_sesi} pada {$hari} pukul {$jam}. "
            .'Pilih room lain atau ubah jam sesi.');
    }
}
