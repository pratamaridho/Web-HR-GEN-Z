<?php

namespace App\Modules\Peserta\Actions;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Peserta\Models\Daftar;
use App\Modules\Peserta\Models\Peserta;
use App\Shared\Enums\StatusDaftar;
use Illuminate\Support\Facades\DB;

/**
 * Tambah banyak peserta sekaligus dari teks tempelan (satu peserta per baris:
 * "Nama, email"), opsional langsung didaftarkan ke satu batch.
 *
 * Semua atau tidak sama sekali: bila ada satu baris bermasalah, tidak ada
 * yang disimpan dan setiap masalah disebut dengan nomor barisnya.
 */
class ImporPeserta
{
    /**
     * @return array{galat: list<string>, jumlah: int}
     */
    public function execute(string $teks, ?Batch $batch = null): array
    {
        $baris = [];
        $galat = [];
        $emailDiBaris = [];

        foreach (preg_split('/\R/', $teks) as $indeks => $mentah) {
            $nomor = $indeks + 1;
            $mentah = trim($mentah);

            if ($mentah === '') {
                continue;
            }

            $bagian = preg_split('/\s*[,;\t]\s*/', $mentah, 2);

            if (count($bagian) !== 2 || trim($bagian[0]) === '' || trim($bagian[1]) === '') {
                $galat[] = "Baris {$nomor}: tulis dengan format \"Nama, email\".";

                continue;
            }

            [$nama, $email] = [trim($bagian[0]), trim($bagian[1])];

            if (mb_strlen($nama) > 50) {
                $galat[] = "Baris {$nomor}: nama maksimal 50 karakter.";
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
                $galat[] = "Baris {$nomor}: format email \"{$email}\" belum benar.";
            } elseif (isset($emailDiBaris[mb_strtolower($email)])) {
                $galat[] = "Baris {$nomor}: email {$email} sudah ada di baris {$emailDiBaris[mb_strtolower($email)]}.";
            } else {
                $emailDiBaris[mb_strtolower($email)] = $nomor;
                $baris[] = ['nomor' => $nomor, 'nama' => $nama, 'email' => $email];
            }
        }

        if ($baris === [] && $galat === []) {
            return ['galat' => ['Tempel minimal satu baris dengan format "Nama, email".'], 'jumlah' => 0];
        }

        $pemilik = Peserta::whereIn('email', array_column($baris, 'email'))->pluck('nama', 'email');

        foreach ($baris as $b) {
            if (isset($pemilik[$b['email']])) {
                $galat[] = "Baris {$b['nomor']}: email {$b['email']} sudah tercatat atas nama {$pemilik[$b['email']]}.";
            }
        }

        if ($galat !== []) {
            sort($galat, SORT_NATURAL);

            return ['galat' => $galat, 'jumlah' => 0];
        }

        DB::transaction(function () use ($baris, $batch) {
            foreach ($baris as $b) {
                $peserta = Peserta::create(['nama' => $b['nama'], 'email' => $b['email']]);

                if ($batch !== null) {
                    Daftar::create([
                        'id_batch' => $batch->id_batch,
                        'id_peserta' => $peserta->id_peserta,
                        'status' => StatusDaftar::Registered->value,
                    ]);
                }
            }
        });

        return ['galat' => [], 'jumlah' => count($baris)];
    }
}
