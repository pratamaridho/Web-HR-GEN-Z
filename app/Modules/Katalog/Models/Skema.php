<?php

namespace App\Modules\Katalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skema extends Model
{
    use HasFactory;

    protected $table = 'tbl_skema';

    protected $primaryKey = 'id_skema';

    public $timestamps = false;

    protected $fillable = [
        'nama_skema',
        'durasi_online',
        'durasi_offline',
    ];

    protected $casts = [
        'durasi_online' => 'decimal:1',
        'durasi_offline' => 'decimal:1',
    ];

    /** Nama lengkap dari config; bila belum terdaftar dipakai kodenya. */
    public function getNamaLengkapAttribute(): string
    {
        return config('hrgz.skema.'.$this->nama_skema, $this->nama_skema);
    }

    /** @return HasMany<Batch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'id_skema', 'id_skema');
    }
}
