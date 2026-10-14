<?php

namespace App\Modules\Kehadiran\Policies;

use App\Modules\Akun\Models\User;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Shared\Enums\RoleUser;

class KehadiranPolicy
{
    /**
     * Hanya Trainer pemilik sesi yang mencatat kehadiran (FR 11).
     * Admin tidak mencatat; Admin mengoreksi lewat koreksi() (FR 12).
     */
    public function input(User $user, Sesi $sesi): bool
    {
        return $user->role === RoleUser::Trainer->value
            && $user->trainer_id !== null
            && (int) $sesi->id_trainer === (int) $user->trainer_id;
    }

    /**
     * Admin bisa mengoreksi kehadiran mana pun (FR 12).
     */
    public function koreksi(User $user): bool
    {
        return $user->role === RoleUser::Admin->value;
    }

    /**
     * Rekap kehadiran adalah halaman Admin (FR 12).
     */
    public function viewRekap(User $user): bool
    {
        return $user->role === RoleUser::Admin->value;
    }
}
