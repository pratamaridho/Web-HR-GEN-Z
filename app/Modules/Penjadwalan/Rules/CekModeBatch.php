<?php

namespace App\Modules\Penjadwalan\Rules;

use App\Modules\Katalog\Models\Batch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CekModeBatch implements ValidationRule
{
    /**
     * @param  array<int>  $idBatches  ID batch yang akan dikaitkan ke satu sesi
     */
    public function __construct(
        private readonly array $idBatches,
    ) {}

    /**
     * Tolak jika batch-batch yang dipilih memiliki mode yang berbeda-beda.
     * Satu sesi hanya boleh menggabungkan batch dengan mode yang sama.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->idBatches)) {
            return;
        }

        $modes = Batch::whereIn('id_batch', $this->idBatches)
            ->distinct()
            ->pluck('mode');

        if ($modes->count() > 1) {
            $fail('Semua batch yang dipilih harus memiliki mode yang sama (Online atau Offline); tidak boleh dicampur (konflik mode).');
        }
    }
}
