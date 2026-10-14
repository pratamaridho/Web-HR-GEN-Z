<?php

namespace Tests\Feature\Akun;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Trainer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * FR 01: tiga akun dummy dibuat lewat seeder (README mencatat password-nya).
 */
class AkunSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeder_membuat_tiga_akun_dengan_peran_masing_masing(): void
    {
        $this->assertSame(3, User::count());
        $this->assertSame('admin', User::where('email', 'admin@example.test')->value('role'));
        $this->assertSame('trainer', User::where('email', 'trainer@example.test')->value('role'));
        $this->assertSame('management', User::where('email', 'management@example.test')->value('role'));
    }

    public function test_akun_trainer_terhubung_ke_trainer_pertama_dan_lainnya_tidak(): void
    {
        $trainerPertama = Trainer::orderBy('id_trainer')->firstOrFail();

        $this->assertSame(
            $trainerPertama->id_trainer,
            User::where('email', 'trainer@example.test')->value('trainer_id')
        );
        $this->assertNull(User::where('email', 'admin@example.test')->value('trainer_id'));
        $this->assertNull(User::where('email', 'management@example.test')->value('trainer_id'));
    }

    public function test_password_akun_disimpan_sebagai_hash(): void
    {
        $akun = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertNotSame('password', $akun->password);
        $this->assertTrue(Hash::check('password', $akun->password));
    }
}
