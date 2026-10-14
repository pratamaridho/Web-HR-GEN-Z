<?php

namespace App\Modules\Kehadiran\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Kehadiran extends Pivot
{
    protected $table = 'tbl_kehadiran';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id_peserta',
        'id_sesi',
        'status_hadir',
    ];
}
