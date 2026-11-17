# Implementasi Laravel – Class Diagram Modul 3

Paket ini berisi **migration (DDL), model, controller, route, middleware, command, seeder**, dan view PDF.
View Blade untuk kelas `<<boundary>>` (halaman/form) belum dibuat; controller sudah memanggil nama view-nya
(mis. `katalog.index`, `pic.pengajuan.show`).

> Kode ini belum dijalankan (lingkungan penyusun tidak punya PHP). Jalankan `php artisan migrate --seed`
> dan laporkan error bila ada.

## Cara memasang (Laravel 11/12, PHP 8.2+)

1. Buat proyek: `composer create-project laravel/laravel nama-proyek`
2. Atur `.env`: koneksi MySQL (`DB_*`) dan `APP_TIMEZONE=Asia/Jakarta`
3. Pasang paket PDF: `composer require barryvdh/laravel-dompdf`
4. Salin isi folder ini ke proyek (timpa `app/Models/User.php`, `routes/web.php`, `database/seeders/DatabaseSeeder.php`).
5. Daftarkan alias middleware di `bootstrap/app.php`:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias(['role' => \App\Http\Middleware\CheckRole::class]);
   })
   ```
6. Tambahkan scheduler di `routes/console.php`:
   ```php
   use Illuminate\Support\Facades\Schedule;
   Schedule::command('pengingat:kirim')->everyMinute();
   ```
   (di server: cron `* * * * * php artisan schedule:run`)
7. `php artisan migrate --seed`

Akun awal: `admin@bem.test` / `password`. Kode akses registrasi: `PIC-2026` dan `ADM-2026` (ganti di seeder).

## Pemetaan class diagram → Laravel

| Class diagram | Laravel |
|---|---|
| Mahasiswa, pic, ProgramKerja, Pendaftaran, Pengingat, LaporanKegiatan | Model + tabel `mahasiswa`, `pic`, `program_kerja`, `pendaftaran`, `pengingat`, `laporan_kegiatan` |
| PengajuanModel | Model `Pengajuan`, tabel `pengajuan` |
| NotifikasiService | Model `Notifikasi`, tabel `notifikasi` |
| `<<controller>>` | Controller dengan nama sama; PengingatController + command `pengingat:kirim` |
| `<<boundary>>` | View Blade (tahap berikutnya) |
| Aktor Super Admin & kode akses | `users.role = ADM`, tabel `kode_akses` |

## Penyesuaian terhadap modul (perlu Anda ketahui)

- **Tabel `users`**: nama, email, password, dan role disimpan di `users` (autentikasi bawaan Laravel). Tabel `mahasiswa` dan `pic`
  hanya menyimpan data khusus peran. Password di-hash (panjang 60+ karakter), jadi tidak bisa `varchar(20)` seperti di modul.
- **`pendaftaran`**: unique pada pasangan `(id_mahasiswa, id_proker)`. Di modul `idMahasiswa` dan `tanggalPendaftaran` unique sendiri-sendiri,
  yang membuat satu mahasiswa hanya bisa mendaftar sekali seumur hidup.
- **`kuotaTersedia` dan `statusPendaftaran` (ProgramKerja)** tidak disimpan sebagai kolom, tetapi dihitung (`cekKuotaTersedia()`, `cekStatusPendaftaran()`).
  `status_buka` tetap kolom (PIC buka/tutup), ditambah `dipublikasi` (Admin, UC-16).
- **`pengajuan.id_proker`** dibuat NOT NULL + unique: program kerja dibuat dulu sebagai Draft, baru diajukan.
  Kolom berkas (`berkas_proposal`, `berkas_rab`, `berkas_surat_pengantar`, `link_bukti`) dan `catatan` penolakan ditambahkan dari sketsa UI.
- **Status setelah PIC konfirmasi**: pseudo-code PC-10-02 menulis "Disetujui" di proses tetapi "Menunggu Konfirmasi" di output. Kode ini mengikuti output
  (Admin yang menyetujui, UC-15). Sebaiknya rapikan pseudo-code di modul.
- **`laporan_kegiatan`**: memakai atribut dari class diagram (anggaran, evaluasi, jumlah hadir, tanggal realisasi); `nama_proker` dihapus karena redundan.
- **Nilai enum `kategori`** (Lomba, Seminar, Workshop, Pengabdian, Lainnya) hanya contoh; ubah di `app/Enums/KategoriProker.php` sebelum migrate.
- Ekspor "Excel" berupa CSV (bisa dibuka di Excel). Untuk `.xlsx` asli, pakai `maatwebsite/excel`.

## Langkah berikutnya

1. Jalankan migration dan uji alur lewat `php artisan tinker`.
2. Buat view Blade per boundary (login/registrasi, katalog, form pendaftaran, form proker, halaman pengajuan, rekap).
3. Halaman daftar notifikasi (tabel `notifikasi` sudah terisi dari pengajuan, keputusan Admin, dan pengingat).
4. Feature test untuk alur pendaftaran (kuota, duplikat) dan pengajuan.
