<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data contoh lengkap: akun, skema, trainer, siklus online Oktober 2026,
     * dan skenario offline November 2026.
     * Untuk mulai dari kosong pakai --seeder=DataAwalSeeder.
     */
    public function run(): void
    {
        $this->call([
            DataAwalSeeder::class,
            DataContohSeeder::class,
            MasterScheduleSeeder::class,
        ]);
    }
}
