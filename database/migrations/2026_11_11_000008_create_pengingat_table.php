<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat', function (Blueprint $table) {
            $table->increments('id_pengingat');
            $table->unsignedInteger('id_mahasiswa');
            $table->unsignedInteger('id_proker');
            $table->dateTime('waktu_pengingat');
            $table->boolean('status_pengingat')->default(true);
            $table->dateTime('terkirim_pada')->nullable(); // diisi saat notifikasi sudah dikirim
            $table->timestamps();

            $table->foreign('id_mahasiswa')->references('id_mahasiswa')->on('mahasiswa')->cascadeOnDelete();
            $table->foreign('id_proker')->references('id_proker')->on('program_kerja')->cascadeOnDelete();
            $table->unique(['id_mahasiswa', 'id_proker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat');
    }
};
