# Sistem Penjadwalan Pelatihan HRGZ

Aplikasi web untuk menyusun jadwal enam skema pelatihan HR GenZ Academy dalam satu siklus bulanan.

## Cara menjalankan

Butuh PHP 8.2 atau lebih baru, Composer, dan Node.js. Node.js dipakai untuk membangun tampilan (`npm run build`); tanpa itu halaman tampil tanpa gaya.

```bash
composer install
npm install
npm run build

cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # Windows PowerShell: New-Item database/database.sqlite

php artisan migrate:fresh --seed
php artisan serve
```

Buka http://127.0.0.1:8000.

Data awal tanpa data contoh (akun, skema, dan trainer saja):

```bash
php artisan migrate:fresh --seed --seeder=DataAwalSeeder
```

`migrate:fresh` menghapus semua tabel. Jalankan hanya di database lokal.

## Akun

Kata sandi semua akun: `password`.

| Peran | Email |
| --- | --- |
| Admin | admin@example.test |
| Trainer | trainer@example.test |
| Management | management@example.test |

## Tes

```bash
php artisan test
```
