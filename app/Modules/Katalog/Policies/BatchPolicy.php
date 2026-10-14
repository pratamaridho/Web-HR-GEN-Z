<?php

namespace App\Modules\Katalog\Policies;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Shared\Enums\RoleUser;

class BatchPolicy
{
    /**
     * Daftar batch hanya untuk Admin. Management melihat angka batch aktif
     * lewat dashboard, bukan lewat data master (PRD bagian 3).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === RoleUser::Admin->value;
    }

    public function create(User $user): bool
    {
        return $user->role === RoleUser::Admin->value;
    }

    public function update(User $user, Batch $batch): bool
    {
        return $user->role === RoleUser::Admin->value;
    }

    public function delete(User $user, Batch $batch): bool
    {
        return false; // FR: histori batch tidak boleh dihapus (restrict)
    }
}
