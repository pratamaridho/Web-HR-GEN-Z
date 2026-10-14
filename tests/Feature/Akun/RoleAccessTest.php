<?php

namespace Tests\Feature\Akun;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Kehadiran\Models\Kehadiran;
use App\Modules\Penjadwalan\Models\Sesi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * FR 01 (Login dan peran), AC 01, UAT 01.
 *
 * Pemeriksaan dilakukan di server: rute, Gate, dan Policy.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function buatTrainer(string $nama = 'Trainer Uji'): Trainer
    {
        return Trainer::create(['nama' => $nama, 'kompetensi' => 'BP, JWP']);
    }

    private function akunTrainer(?Trainer $trainer = null): User
    {
        return User::factory()->trainer($trainer ?? $this->buatTrainer())->create();
    }

    // --- Login dan logout (AC 01 a, b, e) --------------------------------

    public function test_admin_dan_management_masuk_ke_dashboard_setelah_login(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->management()->create()] as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_trainer_masuk_ke_jadwal_saya_setelah_login(): void
    {
        $user = $this->akunTrainer();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('jadwal-saya', absolute: false));
    }

    public function test_kredensial_salah_menghasilkan_pesan_yang_sama_untuk_email_terdaftar_dan_tidak(): void
    {
        $user = User::factory()->admin()->create();
        $pesan = 'Email atau password salah. Periksa lagi lalu coba masuk kembali.';

        $passwordSalah = $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah-total']);
        $emailTidakAda = $this->from('/login')
            ->post('/login', ['email' => 'tidak.ada@example.test', 'password' => 'salah-total']);

        $this->assertGuest();
        $passwordSalah->assertSessionHasErrors(['email' => $pesan]);
        $emailTidakAda->assertSessionHasErrors(['email' => $pesan]);
    }

    public function test_isian_kosong_menampilkan_pesan_per_kolom(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Isi email.', 'password' => 'Isi password.']);
    }

    public function test_logout_mengakhiri_sesi_dan_menampilkan_pesan_keluar_di_halaman_login(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/logout')
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('keluar', true);

        $this->assertGuest();

        // Pesan hanya tampil sekali (flash), tidak menempel di kunjungan berikutnya.
        $this->get('/login')->assertOk()->assertSee('Anda sudah keluar.');
        $this->get('/login')->assertOk()->assertDontSee('Anda sudah keluar.');
        $this->get('/dashboard')->assertRedirect(route('login', absolute: false));
    }

    public function test_pengguna_tanpa_login_dialihkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login', absolute: false));
        $this->get('/jadwal-saya')->assertRedirect(route('login', absolute: false));
        $this->get('/')->assertRedirect(route('login', absolute: false));
    }

    public function test_halaman_awal_mengarahkan_pengguna_login_ke_beranda_perannya(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
        $this->actingAs($this->akunTrainer())->get('/')
            ->assertRedirect(route('jadwal-saya', absolute: false));
    }

    public function test_pengguna_yang_sudah_login_tidak_melihat_halaman_login_lagi(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/login')
            ->assertRedirect();
    }

    public function test_fitur_akun_di_luar_prd_tidak_tersedia(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.test']);

        $this->get('/forgot-password')->assertNotFound();
        $this->get('/reset-password/token')->assertNotFound();

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/profile')->assertNotFound();
        $this->delete('/profile')->assertNotFound();
        $this->get('/verify-email')->assertNotFound();
        $this->get('/confirm-password')->assertNotFound();
    }

    // --- Pembatasan halaman per peran di server (AC 01 c, d) -------------

    public function test_dashboard_terbuka_untuk_admin_dan_management_tetapi_ditolak_untuk_trainer(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertOk();
        $this->actingAs(User::factory()->management()->create())->get('/dashboard')->assertOk();
        $this->actingAs($this->akunTrainer())->get('/dashboard')->assertForbidden();
    }

    public function test_jadwal_saya_hanya_untuk_trainer(): void
    {
        $this->actingAs($this->akunTrainer())->get('/jadwal-saya')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/jadwal-saya')->assertForbidden();
        $this->actingAs(User::factory()->management()->create())->get('/jadwal-saya')->assertForbidden();
    }

    public function test_halaman_dan_aksi_admin_ditolak_untuk_trainer_dan_management(): void
    {
        // Modul admin belum ada; rute uji memakai middleware yang sama
        // dengan yang akan dipasang pada halaman admin (can:manage-operations).
        Route::middleware(['web', 'auth', 'can:manage-operations'])->group(function () {
            Route::get('/uji/halaman-admin', fn () => 'ok');
            Route::post('/uji/ubah-data', fn () => 'ok');
        });

        $this->actingAs($this->akunTrainer())->get('/uji/halaman-admin')->assertForbidden();
        $this->actingAs(User::factory()->management()->create())->post('/uji/ubah-data')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/uji/halaman-admin')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->post('/uji/ubah-data')->assertOk();
    }

    public function test_halaman_ditolak_sesuai_desain(): void
    {
        $this->actingAs($this->akunTrainer())
            ->get('/dashboard')
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('Peran Trainer tidak boleh membuka halaman ini.')
            ->assertSee('Ke halaman saya')
            ->assertSee(route('jadwal-saya'), false);
    }

    // --- Menu sesuai peran (UAT 01) --------------------------------------

    public function test_menu_hanya_memuat_halaman_yang_boleh_dibuka_peran(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertSee('data-menu="dashboard"', false)
            ->assertDontSee('data-menu="jadwal-saya"', false);

        $this->actingAs(User::factory()->management()->create())
            ->get('/dashboard')
            ->assertSee('data-menu="dashboard"', false)
            ->assertDontSee('data-menu="jadwal-saya"', false);

        $this->actingAs($this->akunTrainer())
            ->get('/jadwal-saya')
            ->assertSee('data-menu="jadwal-saya"', false)
            ->assertDontSee('data-menu="dashboard"', false);
    }

    public function test_sidebar_menampilkan_nama_peran_dan_tombol_keluar(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Sari Anggraini']);

        $this->actingAs($user)->get('/dashboard')
            ->assertSee('Sari Anggraini')
            ->assertSee('Admin Operasional')
            ->assertSee('Keluar');
    }

    // --- Gate dan Policy sesuai PRD --------------------------------------

    public function test_gate_per_peran(): void
    {
        $admin = User::factory()->admin()->make();
        $management = User::factory()->management()->make();
        $trainer = User::factory()->trainer($this->buatTrainer())->make();

        $this->assertTrue(Gate::forUser($admin)->allows('manage-operations'));
        $this->assertFalse(Gate::forUser($management)->allows('manage-operations'));
        $this->assertFalse(Gate::forUser($trainer)->allows('manage-operations'));

        $this->assertTrue(Gate::forUser($admin)->allows('view-reports'));
        $this->assertTrue(Gate::forUser($management)->allows('view-reports'));
        $this->assertFalse(Gate::forUser($trainer)->allows('view-reports'));

        $this->assertTrue(Gate::forUser($trainer)->allows('trainer'));
        $this->assertFalse(Gate::forUser($admin)->allows('trainer'));
        $this->assertFalse(Gate::forUser($management)->allows('trainer'));
    }

    public function test_management_hanya_membaca_dashboard(): void
    {
        $management = User::factory()->management()->make();

        $this->assertFalse(Gate::forUser($management)->allows('view-schedules'));
        $this->assertFalse(Gate::forUser($management)->allows('viewAny', Sesi::class));
        $this->assertFalse(Gate::forUser($management)->allows('viewAny', Batch::class));
        $this->assertFalse(Gate::forUser($management)->allows('viewRekap', Kehadiran::class));
    }

    public function test_akun_trainer_tanpa_data_trainer_tidak_punya_akses_trainer(): void
    {
        $user = User::factory()->make(['role' => 'trainer', 'trainer_id' => null]);

        $this->assertFalse(Gate::forUser($user)->allows('trainer'));
    }

    public function test_trainer_hanya_boleh_mencatat_kehadiran_pada_sesinya_sendiri(): void
    {
        $trainerA = $this->buatTrainer('Trainer A');
        $trainerB = $this->buatTrainer('Trainer B');
        $userA = User::factory()->trainer($trainerA)->create();

        $sesiMilikA = (new Sesi)->forceFill(['id_trainer' => $trainerA->id_trainer]);
        $sesiMilikB = (new Sesi)->forceFill(['id_trainer' => $trainerB->id_trainer]);

        $this->assertTrue(Gate::forUser($userA)->allows('input', [Kehadiran::class, $sesiMilikA]));
        $this->assertFalse(Gate::forUser($userA)->allows('input', [Kehadiran::class, $sesiMilikB]));
    }

    public function test_hanya_trainer_pemilik_sesi_yang_mencatat_kehadiran_bukan_admin_atau_management(): void
    {
        $sesi = (new Sesi)->forceFill(['id_trainer' => 1]);

        $this->assertFalse(Gate::forUser(User::factory()->admin()->make())->allows('input', [Kehadiran::class, $sesi]));
        $this->assertFalse(Gate::forUser(User::factory()->management()->make())->allows('input', [Kehadiran::class, $sesi]));
        $this->assertFalse(Gate::forUser(
            User::factory()->make(['role' => 'trainer', 'trainer_id' => null])
        )->allows('input', [Kehadiran::class, $sesi]));
    }

    public function test_admin_tetap_dapat_mengoreksi_kehadiran(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->admin()->make())->allows('koreksi', Kehadiran::class));
        $this->assertFalse(Gate::forUser(User::factory()->management()->make())->allows('koreksi', Kehadiran::class));
    }

    // --- Model dan data peran --------------------------------------------

    public function test_model_autentikasi_adalah_user_modul_akun(): void
    {
        $this->assertSame(User::class, Auth::getProvider()->getModel());

        $this->actingAs(User::factory()->admin()->create());
        $this->assertInstanceOf(User::class, Auth::user());
    }

    public function test_role_dan_trainer_id_tidak_dapat_diisi_lewat_mass_assignment(): void
    {
        $user = new User([
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'role' => 'admin',
            'trainer_id' => 1,
        ]);

        $this->assertNull($user->role);
        $this->assertNull($user->trainer_id);
    }

    public function test_akun_tanpa_peran_eksplisit_tidak_pernah_menjadi_admin(): void
    {
        DB::table('users')->insert([
            'name' => 'Tanpa Peran',
            'email' => 'tanpa.peran@example.test',
            'password' => 'x',
        ]);

        $this->assertSame('management', DB::table('users')->where('email', 'tanpa.peran@example.test')->value('role'));
    }

    public function test_akun_trainer_terhubung_ke_satu_data_trainer(): void
    {
        $trainer = $this->buatTrainer();
        $user = User::factory()->trainer($trainer)->create();

        $this->assertTrue($user->trainer->is($trainer));
        $this->assertTrue($trainer->user->is($user));
    }
}
