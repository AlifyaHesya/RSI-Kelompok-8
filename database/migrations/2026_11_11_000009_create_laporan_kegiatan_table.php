<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_kegiatan', function (Blueprint $table) {
            $table->increments('id_laporan');
            $table->unsignedInteger('id_proker')->unique(); // maksimal 1 laporan per proker
            $table->text('ringkasan_kegiatan');
            $table->text('kendala')->nullable();
            $table->text('evaluasi')->nullable();
            $table->decimal('anggaran_realisasi', 15, 2)->nullable();
            $table->unsignedInteger('jumlah_peserta_hadir')->nullable();
            $table->date('tanggal_realisasi')->nullable();
            $table->dateTime('tanggal_dibuat');
            $table->dateTime('tanggal_publikasi')->nullable();
            $table->timestamps();

            $table->foreign('id_proker')->references('id_proker')->on('program_kerja')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kegiatan');
    }
};
