<?php

namespace App\Modules\Penjadwalan\Models;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Peserta\Models\Peserta;
use App\Shared\Enums\StatusSesi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sesi extends Model
{
    use HasFactory;

    protected $table = 'tbl_sesi';

    protected $primaryKey = 'id_sesi';

    public $timestamps = false;

    protected $fillable = [
        'id_trainer',
        'nama_sesi',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'durasi_hari',
        'room',
        'tipe',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'durasi_hari' => 'decimal:1',
    ];

    /** Saat sesi mulai (tanggal dan jam mulai). */
    public function getMulaiAttribute(): Carbon
    {
        return $this->tanggal->copy()->setTimeFromTimeString($this->jam_mulai);
    }

    /** Saat sesi selesai (tanggal dan jam selesai). */
    public function getSelesaiAttribute(): Carbon
    {
        return $this->tanggal->copy()->setTimeFromTimeString($this->jam_selesai);
    }

    /**
     * Trainer tidak lagi bisa mencatat kehadiran bila sesi dibatalkan atau
     * sudah Completed lebih dari 24 jam. Admin tetap bisa mengoreksi.
     */
    public function kehadiranTerkunci(): bool
    {
        if ($this->status === StatusSesi::Cancelled->value) {
            return true;
        }

        return $this->status === StatusSesi::Completed->value
            && now()->greaterThan($this->selesai->copy()->addDay());
    }

    /** @return BelongsTo<Trainer, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'id_trainer', 'id_trainer');
    }

    /** @return BelongsToMany<Batch, $this> */
    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'tbl_sesi_batch', 'id_sesi', 'id_batch');
    }

    /** @return BelongsToMany<Peserta, $this> */
    public function peserta(): BelongsToMany
    {
        return $this->belongsToMany(Peserta::class, 'tbl_kehadiran', 'id_sesi', 'id_peserta')
            ->withPivot('status_hadir');
    }
}
