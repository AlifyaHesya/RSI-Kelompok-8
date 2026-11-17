<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->increments('id_pendaftaran');
            $table->unsignedInteger('id_mahasiswa');
            $table->unsignedInteger('id_proker');
            $table->dateTime('tanggal_pendaftaran');
            $table->boolean('status_pendaftaran')->default(true);
            $table->timestamps();

            $table->foreign('id_mahasiswa')->references('id_mahasiswa')->on('mahasiswa')->cascadeOnDelete();
            $table->foreign('id_proker')->references('id_proker')->on('program_kerja')->cascadeOnDelete();
            // satu mahasiswa hanya boleh mendaftar sekali pada satu proker
            $table->unique(['id_mahasiswa', 'id_proker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran');
    }
};
