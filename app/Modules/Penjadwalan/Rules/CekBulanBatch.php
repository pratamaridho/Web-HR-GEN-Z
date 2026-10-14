<?php

namespace App\Modules\Penjadwalan\Rules;

use App\Modules\Katalog\Models\Batch;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CekBulanBatch implements ValidationRule
{
    /**
     * @param  array<int|string>  $idBatch
     */
    public function __construct(private readonly array $idBatch) {}

    /**
     * Tolak jika tanggal sesi berada di luar bulan salah satu batch yang
     * dilayani. Batch November tidak bisa dijadwalkan pada tanggal Oktober.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || $this->idBatch === []) {
            return;
        }

        $salah = Batch::whereIn('id_batch', array_map('intval', $this->idBatch))
            ->where('periode', '!=', substr($value, 0, 7))
            ->orderBy('periode')
            ->first();

        if ($salah === null) {
            return;
        }

        $tanggal = Carbon::parse($value)->translatedFormat('j F Y');
        $bulan = Carbon::createFromFormat('!Y-m', $salah->periode)->translatedFormat('F Y');

        $fail("{$salah->nama_siklus} tidak bisa dijadwalkan pada {$tanggal}. "
            ."Pilih tanggal di bulan {$bulan} atau ganti batch.");
    }
}
