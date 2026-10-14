<?php

namespace Tests\Feature\Katalog;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Trainer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TambahTrainerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function sebagai(string $email): static
    {
        return $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    public function test_admin_menambah_trainer(): void
    {
        $this->sebagai('admin@example.test')
            ->post('/data-master/trainer', ['nama' => 'Trainer 4', 'kompetensi' => 'Basic Programming'])
            ->assertRedirect();

        $this->assertDatabaseHas('tbl_trainer', ['nama' => 'Trainer 4', 'kompetensi' => 'Basic Programming']);
    }

    public function test_nama_ganda_ditolak_dengan_pesan_jelas(): void
    {
        $sudahAda = Trainer::first()->nama;

        $this->sebagai('admin@example.test')
            ->post('/data-master/trainer', ['nama' => $sudahAda, 'kompetensi' => 'BP'])
            ->assertSessionHasErrors('nama');
    }

    public function test_kompetensi_wajib_diisi(): void
    {
        $this->sebagai('admin@example.test')
            ->post('/data-master/trainer', ['nama' => 'Trainer 5', 'kompetensi' => ''])
            ->assertSessionHasErrors('kompetensi');
    }

    public function test_trainer_dan_management_tidak_boleh_menambah(): void
    {
        $this->sebagai('trainer@example.test')
            ->post('/data-master/trainer', ['nama' => 'X', 'kompetensi' => 'Y'])->assertForbidden();
        $this->sebagai('management@example.test')
            ->post('/data-master/trainer', ['nama' => 'X', 'kompetensi' => 'Y'])->assertForbidden();
    }
}
