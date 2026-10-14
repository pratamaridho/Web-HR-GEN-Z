<?php

namespace App\Modules\Akun\Models;

use App\Modules\Katalog\Models\Trainer;
use App\Shared\Enums\RoleUser;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun pengguna sistem (admin, trainer, management). Peserta tidak punya akun.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * `role` dan `trainer_id` sengaja tidak mass-assignable agar peran tidak
     * dapat diubah lewat input form. Keduanya hanya diisi oleh seeder atau
     * kode yang menetapkannya secara eksplisit (forceFill / forceCreate).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @return BelongsTo<Trainer, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id', 'id_trainer');
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleUser::Admin->value;
    }

    public function isTrainer(): bool
    {
        return $this->role === RoleUser::Trainer->value;
    }

    public function isManagement(): bool
    {
        return $this->role === RoleUser::Management->value;
    }

    /**
     * Peran akun, atau null bila nilai di database tidak dikenal.
     */
    public function peran(): ?RoleUser
    {
        return RoleUser::tryFrom((string) $this->role);
    }

    /**
     * Nama rute beranda sesuai peran. Peran tak dikenal diperlakukan
     * sebagai yang paling sempit (Management).
     */
    public function homeRouteName(): string
    {
        return ($this->peran() ?? RoleUser::Management)->homeRoute();
    }
}
