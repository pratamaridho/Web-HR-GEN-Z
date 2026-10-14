<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilihBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_sesi_menyediakan_jalan_pintas_sesuai_aturan_bisnis(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->get('/form-sesi')
            ->assertOk()
            ->assertSee('Semua skema')
            ->assertSee('JNA + NA')
            ->assertSee('TMJK + TUJK')
            ->assertSee('data-kode="BP"', false)
            ->assertSee('Junior Web Programmer');
    }
}
