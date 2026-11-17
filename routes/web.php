<?php

use App\Enums\Role;
use App\Http\Controllers\AdminPengajuanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PengingatController;
use App\Http\Controllers\ProgramKerjaController;
use App\Http\Controllers\RekapLaporanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('katalog.index'));

/* ---------- UC-01 / UC-02: Auth ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'pilihRole'])->name('register');
    Route::post('/register/kode-akses', [AuthController::class, 'cekKodeAkses'])->name('register.kode');
    Route::get('/register/{role}', [AuthController::class, 'showRegister'])->name('register.form');
    Route::post('/register/{role}', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Halaman utama sesuai role setelah login
Route::get('/dashboard', fn () => redirect()->route(match (auth()->user()->role) {
    Role::Mahasiswa => 'katalog.index',
    Role::Pic => 'pic.proker.index',
    Role::Admin => 'admin.pengajuan.index',
}))->middleware('auth')->name('dashboard');

/* ---------- UC-03/04/05/08: Katalog (publik) ---------- */
Route::get('/proker', [KatalogController::class, 'index'])->name('katalog.index');
Route::get('/proker/{programKerja}', [KatalogController::class, 'show'])->name('katalog.show');

/* ---------- Mahasiswa: UC-06, UC-07 ---------- */
Route::middleware(['auth', 'role:MHS'])->group(function () {
    Route::get('/proker/{programKerja}/daftar', [PendaftaranController::class, 'periksaPendaftaran'])->name('pendaftaran.periksa');
    Route::post('/proker/{programKerja}/daftar', [PendaftaranController::class, 'simpanPendaftaran'])->name('pendaftaran.simpan');
    Route::get('/pendaftaran', [PendaftaranController::class, 'index'])->name('pendaftaran.index');
    Route::get('/pendaftaran/{pendaftaran}', [PendaftaranController::class, 'konfirmasi'])->name('pendaftaran.konfirmasi');

    Route::post('/proker/{programKerja}/pengingat', [PengingatController::class, 'store'])->name('pengingat.store');
});

/* ---------- PIC: UC-09 s.d. UC-13 ---------- */
Route::middleware(['auth', 'role:PIC'])->prefix('pic')->name('pic.')->group(function () {
    Route::resource('proker', ProgramKerjaController::class)
        ->parameters(['proker' => 'programKerja'])
        ->except(['show', 'destroy']);
    Route::patch('proker/{programKerja}/buka', [ProgramKerjaController::class, 'toggleBuka'])->name('proker.buka');

    Route::get('pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('pengajuan/{pengajuan}', [PengajuanController::class, 'show'])->name('pengajuan.show');
    Route::post('pengajuan/{pengajuan}/berkas', [PengajuanController::class, 'unggahBerkas'])->name('pengajuan.berkas');
    Route::post('pengajuan/{pengajuan}/ajukan', [PengajuanController::class, 'submitPengajuan'])->name('pengajuan.ajukan');
    Route::post('pengajuan/{pengajuan}/konfirmasi', [PengajuanController::class, 'prosesPengajuan'])->name('pengajuan.konfirmasi');

    Route::get('rekap', [RekapLaporanController::class, 'index'])->name('rekap.index');
    Route::get('rekap/{programKerja}', [RekapLaporanController::class, 'opsiDokumen'])->name('rekap.opsi');
    Route::get('rekap/{programKerja}/peserta', [RekapLaporanController::class, 'periksaDataPeserta'])->name('rekap.peserta');
    Route::get('rekap/{programKerja}/peserta/unduh', [RekapLaporanController::class, 'buatRekapPeserta'])->name('rekap.peserta.unduh');
    Route::get('rekap/{programKerja}/laporan', [RekapLaporanController::class, 'formLaporan'])->name('rekap.laporan');
    Route::post('rekap/{programKerja}/laporan', [RekapLaporanController::class, 'simpanLaporan'])->name('rekap.laporan.simpan');
    Route::get('laporan/{laporan}/unduh', [RekapLaporanController::class, 'unduhLaporan'])->name('laporan.unduh');
});

/* ---------- Super Admin: UC-14, UC-15, UC-16 ---------- */
Route::middleware(['auth', 'role:ADM'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('pengajuan', [AdminPengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('pengajuan/{pengajuan}', [AdminPengajuanController::class, 'show'])->name('pengajuan.show');
    Route::post('pengajuan/{pengajuan}/setujui', [AdminPengajuanController::class, 'setujui'])->name('pengajuan.setujui');
    Route::post('pengajuan/{pengajuan}/tolak', [AdminPengajuanController::class, 'tolak'])->name('pengajuan.tolak');
    Route::patch('proker/{programKerja}/publikasi', [AdminPengajuanController::class, 'publikasi'])->name('proker.publikasi');
});
