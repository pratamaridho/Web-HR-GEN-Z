<?php

namespace App\Modules\Katalog\Models;

use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Batch extends Model
{
    use HasFactory;

    protected $table = 'tbl_batch';

    protected $primaryKey = 'id_batch';

    public $timestamps = false;

    protected $fillable = [
        'id_skema',
        'periode',
        'mode',
        'status',
    ];

    /**
     * Urutan siklus bulanan: posisi pasangan (periode, mode) batch ini di
     * antara semua siklus pada tahun yang sama. Enam skema pada bulan dan mode
     * yang sama berbagi nomor yang sama. Tidak disimpan di database.
     */
    public function getNomorAttribute(): int
    {
        $siklus = static::where('periode', 'like', substr($this->periode, 0, 4).'-%')
            ->selectRaw('periode, mode, MIN(id_batch) as awal')
            ->groupBy('periode', 'mode')
            ->orderBy('periode')->orderBy('awal')
            ->get();

        $posisi = $siklus->search(fn ($s) => $s->periode === $this->periode && $s->mode === $this->mode);

        return ($posisi === false ? 0 : $posisi) + 1;
    }

    /** Label siklus, misalnya "Batch 1 · Oktober 2026 · Online". */
    public function getNamaSiklusAttribute(): string
    {
        $bulan = Carbon::createFromFormat('!Y-m', $this->periode)->translatedFormat('F Y');

        return "Batch {$this->nomor} · {$bulan} · {$this->mode}";
    }

    /** Label satu skema, misalnya "Batch 1 · Junior Web Programmer 2026 · Online". */
    public function getNamaAttribute(): string
    {
        $tahun = substr($this->periode, 0, 4);

        return "Batch {$this->nomor} · {$this->skema->nama_lengkap} {$tahun} · {$this->mode}";
    }

    /** @return BelongsTo<Skema, $this> */
    public function skema(): BelongsTo
    {
        return $this->belongsTo(Skema::class, 'id_skema', 'id_skema');
    }

    /** @return BelongsToMany<Sesi, $this> */
    public function sesi(): BelongsToMany
    {
        return $this->belongsToMany(Sesi::class, 'tbl_sesi_batch', 'id_batch', 'id_sesi');
    }

    /** @return BelongsToMany<Peserta, $this> */
    public function peserta(): BelongsToMany
    {
        return $this->belongsToMany(Peserta::class, 'tbl_daftar', 'id_batch', 'id_peserta')
            ->withPivot('status');
    }
}
