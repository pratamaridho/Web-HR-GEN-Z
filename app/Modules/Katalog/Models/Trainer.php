<?php

namespace App\Modules\Katalog\Models;

use App\Modules\Akun\Models\User;
use App\Modules\Penjadwalan\Models\Sesi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trainer extends Model
{
    use HasFactory;

    protected $table = 'tbl_trainer';

    protected $primaryKey = 'id_trainer';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'kompetensi',
    ];

    /** @return HasMany<Sesi, $this> */
    public function sesi(): HasMany
    {
        return $this->hasMany(Sesi::class, 'id_trainer', 'id_trainer');
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'trainer_id', 'id_trainer');
    }
}
