<?php

namespace Database\Factories;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Trainer;
use App\Shared\Enums\RoleUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * Peran bawaan adalah yang paling sempit (hanya baca) supaya tes tidak
     * tanpa sengaja memakai akun admin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => RoleUser::Management->value,
            'trainer_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => RoleUser::Admin->value, 'trainer_id' => null]);
    }

    public function management(): static
    {
        return $this->state(fn () => ['role' => RoleUser::Management->value, 'trainer_id' => null]);
    }

    public function trainer(Trainer $trainer): static
    {
        return $this->state(fn () => [
            'role' => RoleUser::Trainer->value,
            'trainer_id' => $trainer->getKey(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
