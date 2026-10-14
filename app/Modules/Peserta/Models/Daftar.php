<?php

namespace App\Modules\Peserta\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Daftar extends Pivot
{
    protected $table = 'tbl_daftar';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id_batch',
        'id_peserta',
        'status',
    ];
}
