<?php

namespace App\Modules\Penjadwalan\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SesiBatch extends Pivot
{
    protected $table = 'tbl_sesi_batch';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id_batch',
        'id_sesi',
    ];
}
