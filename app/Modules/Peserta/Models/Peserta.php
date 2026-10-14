<?php

namespace App\Modules\Peserta\Models;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Penjadwalan\Models\Sesi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Peserta extends Model
{
    use HasFactory;

    protected $table = 'tbl_peserta';

    protected $primaryKey = 'id_peserta';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'email',
    ];

    /** @return BelongsToMany<Batch, $this> */
    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'tbl_daftar', 'id_peserta', 'id_batch')
            ->withPivot('status');
    }

    /** @return BelongsToMany<Sesi, $this> */
    public function sesi(): BelongsToMany
    {
        return $this->belongsToMany(Sesi::class, 'tbl_kehadiran', 'id_peserta', 'id_sesi')
            ->withPivot('status_hadir');
    }
}
