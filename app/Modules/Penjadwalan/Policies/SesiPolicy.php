<?php

namespace App\Modules\Penjadwalan\Policies;

use App\Modules\Akun\Models\User;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Shared\Enums\RoleUser;

class SesiPolicy
{
    /**
     * Admin melihat seluruh jadwal; Trainer melihat jadwal sesinya
     * (penyaringan per trainer dilakukan pada query). Management hanya
     * membaca dashboard (PRD bagian 3).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleUser::Admin->value, RoleUser::Trainer->value], true);
    }

    public function create(User $user): bool
    {
        return $user->role === RoleUser::Admin->value;
    }

    public function update(User $user, Sesi $sesi): bool
    {
        return $user->role === RoleUser::Admin->value;
    }

    public function delete(User $user, Sesi $sesi): bool
    {
        return false; // histori sesi tidak boleh dihapus
    }
}
