<?php

namespace Database\Seeders;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Shared\Enums\RoleUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data awal minimum agar sistem dapat dipakai dari kosong: tiga akun, master
 * enam skema, dan tiga trainer. Peserta, batch, sesi, dan kehadiran sengaja
 * kosong supaya diisi sendiri lewat antarmuka.
 *
 * Skema dan trainer tetap ada karena belum ada halaman untuk membuatnya
 * (data master baca saja, PRD bagian 4), sedangkan batch dan sesi bergantung
 * pada keduanya.
 *
 * Jalankan: php artisan migrate:fresh --seed --seeder=DataAwalSeeder
 * Seeder ini aman dijalankan ulang (idempoten).
 */
class DataAwalSeeder extends Seeder
{
    public const PASSWORD_DUMMY = 'password';

    public function run(): void
    {
        DB::transaction(function () {
            foreach ([
                ['BP', 3, 0.5], ['JNA', 4, 1], ['JWP', 4, 1],
                ['NA', 6, 2], ['TMJK', 4, 1], ['TUJK', 6, 2],
            ] as [$nama, $online, $offline]) {
                Skema::firstOrCreate(
                    ['nama_skema' => $nama],
                    ['durasi_online' => $online, 'durasi_offline' => $offline]
                );
            }

            foreach ([
                ['Trainer 1', 'BP, JWP'], ['Trainer 2', 'JNA, NA'], ['Trainer 3', 'TMJK, TUJK'],
            ] as [$nama, $kompetensi]) {
                Trainer::firstOrCreate(['nama' => $nama], ['kompetensi' => $kompetensi]);
            }

            $trainerPertama = Trainer::orderBy('id_trainer')->firstOrFail();

            foreach ([
                ['Admin HRGZ', 'admin@example.test', RoleUser::Admin, null],
                ['Trainer 1', 'trainer@example.test', RoleUser::Trainer, $trainerPertama->id_trainer],
                ['Management HRGZ', 'management@example.test', RoleUser::Management, null],
            ] as [$nama, $email, $peran, $idTrainer]) {
                $user = User::firstOrNew(['email' => $email]);
                $user->forceFill(['name' => $nama, 'role' => $peran->value, 'trainer_id' => $idTrainer]);

                if (! $user->exists) {
                    $user->password = self::PASSWORD_DUMMY;
                }

                $user->save();
            }
        });
    }
}
