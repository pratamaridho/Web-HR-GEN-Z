# Asumsi dan batasan

Brief tidak menjawab beberapa hal. Ini yang dipilih, supaya jadwal dan data bisa dijelaskan.

## Asumsi

| # | Asumsi | Alasan |
| --- | --- | --- |
| 1 | Satu peserta satu batch per bulan dan mode | Brief hanya melarang peserta ganda. BP didapat lewat sesi bersama, jadi tidak perlu batch BP |
| 2 | 84 peserta, 14 per skema | Brief hanya menyebut 84 pada contoh tampilan |
| 3 | 3 trainer: T1 (BP, JWP), T2 (JNA, NA), T3 (TMJK, TUJK) | Jumlah minimum agar tiga jalur paralel berjalan. Contoh brief menyebut 4 trainer |
| 4 | Kapasitas room dan Zoom tidak dicek | Brief tidak memberi angka. Room disimpan sebagai teks |
| 5 | Satu hari materi dasar gabungan untuk JNA + NA, dan untuk TMJK + TUJK | Brief hanya menyebut materi relevan dapat digabung. **Hasil 6 hari bergantung pada asumsi ini** |
| 6 | Offline 08.30–15.30, BP setengah hari di pagi hari | Brief hanya menyebut 6 sampai 7 jam per hari |
| 7 | Online dan Offline adalah dua skenario alternatif | Bukan dijalankan berurutan dalam satu bulan |
| 8 | Durasi sesi dihitung otomatis: online 1 hari, offline sampai 4 jam 0,5 hari | Menghindari angka yang salah ketik |
| 9 | Ambang lulus 80% dari sesi yang sudah selesai | Brief tidak menyebut ambang. Bisa diubah |
| 10 | Kuota minimum 5 peserta per sesi, dan Trainer mencatat kehadiran sampai 24 jam setelah sesi selesai | Brief tidak memberi angka. Keduanya usulan dan diatur di `config/hrgz.php` |

## Keputusan yang masih terbuka

- BP dijadikan fondasi bersama untuk keenam skema, karena itulah satu-satunya cara memenuhi aturan "digabung dengan lima skema lain" sekaligus mencapai 6 hari. Brief hanya mewajibkan BP untuk JWP. **Perlu dikonfirmasi tim program:** apakah isi BP termasuk dalam kurikulum JNA, NA, TMJK, dan TUJK?
- Bila tidak, peserta non-JWP tidak mengikuti BP, dan jadwal menjadi sekitar 9 hari dengan 3 trainer (atau 6 hari dengan 4 sampai 5 trainer).
- "Learning outcome sama" dinilai dari isi materi, bukan jumlah hari. Sistem tidak menyimpan kurikulum dan hanya mengecek durasi.

## Aturan yang dijaga sistem

- Trainer atau room tidak bisa dipakai dua sesi yang jamnya beririsan pada tanggal yang sama. Sesi berurutan dan sesi Cancelled tidak dianggap bentrok.
- Satu sesi hanya boleh melayani batch dengan mode yang sama.
- Tanggal sesi harus berada di bulan batch yang dilayani.
- Satu peserta satu batch per bulan dan mode. Email peserta unik.
- Satu batch per skema per periode per mode. Batch tidak dihapus, hanya diubah statusnya.
- Status sesi mengikuti waktu: Planned, Ongoing, Completed. Sesi dengan peserta di bawah kuota otomatis Cancelled saat jam mulai.
- Peserta otomatis Completed bila hadir minimal 80% dari sesi yang sudah selesai.

Pengecekan dilakukan di aplikasi supaya pesannya jelas. Constraint database menjadi pagar terakhir untuk jam mulai yang persis sama, email, dan batch. Aturan "satu batch per bulan dan mode" dan pengecekan jam beririsan hanya dijaga aplikasi.

## Belum ada

- Pengingat H-3/H-1.
- Riwayat perubahan (audit trail), backup otomatis, dan ganti kata sandi.
- Pengecekan kapasitas room.
- Mengeluarkan peserta dari batch lewat layar.
- Mengubah atau menghapus skema dan trainer (hanya tambah trainer).
- Impor langsung dari spreadsheet. Spreadsheet dipakai sebagai acuan data.
- Deploy online.
