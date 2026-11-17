<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->increments('id_pengajuan');
            $table->unsignedInteger('id_pic');
            $table->unsignedInteger('id_proker')->unique(); // relasi 1:1 dengan program_kerja
            $table->string('nama_proker', 100);
            $table->string('berkas_proposal')->nullable();
            $table->string('berkas_rab')->nullable();
            $table->string('berkas_surat_pengantar')->nullable();
            $table->string('link_bukti')->nullable();
            $table->boolean('berkas_lengkap')->default(false);
            $table->string('status_pengajuan', 20)->default('Draft');
            $table->text('catatan')->nullable(); // alasan penolakan dari Admin
            $table->timestamps();

            $table->foreign('id_pic')->references('id_pic')->on('pic')->restrictOnDelete();
            $table->foreign('id_proker')->references('id_proker')->on('program_kerja')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }
};
