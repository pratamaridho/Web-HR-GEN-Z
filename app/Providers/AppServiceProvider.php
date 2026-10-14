<?php

namespace App\Providers;

use App\Modules\Akun\Models\User;
use App\Shared\Enums\RoleUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Hak akses mengikuti PRD bagian 3: Admin mengelola semuanya, Trainer
     * hanya jadwal dan kehadiran sesinya sendiri, Management hanya membaca
     * dashboard. Dipakai di server lewat middleware `can:` dan Policy.
     */
    public function boot(): void
    {
        // Cegah N+1: relasi yang belum dimuat dengan with() melempar galat saat pengembangan dan tes.
        Model::preventLazyLoading(! app()->isProduction());

        // Role-based Gates
        Gate::define('admin', fn (User $user) => $user->role === RoleUser::Admin->value);
        Gate::define('management', fn (User $user) => $user->role === RoleUser::Management->value);

        // Trainer hanya bermakna bila akunnya terhubung ke satu data trainer.
        Gate::define('trainer', fn (User $user) => $user->role === RoleUser::Trainer->value
            && $user->trainer_id !== null);

        // Feature / Action Gates
        Gate::define('manage-operations', fn (User $user) => $user->role === RoleUser::Admin->value);
        Gate::define('view-schedules', fn (User $user) => $user->role === RoleUser::Admin->value);
        Gate::define('view-reports', fn (User $user) => in_array(
            $user->role,
            [RoleUser::Admin->value, RoleUser::Management->value],
            true
        ));
    }
}
