<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\KodeAkses;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Kode akses registrasi untuk role PIC dan Super Admin (ganti sebelum dipakai sungguhan!)
        KodeAkses::updateOrCreate(['kode' => 'PIC-2026'], ['role' => 'PIC', 'aktif' => true]);
        KodeAkses::updateOrCreate(['kode' => 'ADM-2026'], ['role' => 'ADM', 'aktif' => true]);

        // Akun Super Admin awal untuk pengujian
        User::updateOrCreate(
            ['email' => 'admin@bem.test'],
            ['name' => 'Super Admin BEM', 'password' => 'password', 'role' => Role::Admin]
        );
    }
}
