<?php

namespace App\Modules\Penjadwalan\Rules;

use App\Modules\Penjadwalan\Models\Sesi;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CekBentrokTrainer implements ValidationRule
{
    public function __construct(
        private readonly int $idTrainer,
        private readonly string $tanggal,
        private readonly string $jamMulai,
        private readonly string $jamSelesai,
        private readonly ?int $kecualiIdSesi = null,
    ) {}

    /**
     * Tolak jika trainer sudah punya sesi yang jamnya beririsan pada tanggal
     * yang sama. Sesi Cancelled tidak menghalangi. Sesi yang berurutan (selesai 19.30, mulai 19.30) tidak
     * dianggap bentrok. `tanggal` disimpan sebagai datetime oleh cast date,
     * jadi dibandingkan dengan whereDate.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bentrok = Sesi::with('trainer')
            ->where('id_trainer', $this->idTrainer)
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

        $fail("Trainer bentrok. {$bentrok->trainer->nama} sudah mengajar {$bentrok->nama_sesi} pada {$hari} pukul {$jam}. "
            .'Pilih trainer lain atau ubah jam sesi.');
    }
}
