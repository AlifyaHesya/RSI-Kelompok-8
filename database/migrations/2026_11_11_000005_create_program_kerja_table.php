<?php

use App\Enums\KategoriProker;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_kerja', function (Blueprint $table) {
            $table->increments('id_proker');
            $table->unsignedInteger('id_pic');
            $table->string('nama_proker', 100);
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('kuota');
            $table->string('lokasi', 100);
            $table->string('biro', 100);
            $table->enum('kategori', collect(KategoriProker::cases())->pluck('value')->all());
            $table->date('tanggal_pelaksanaan');
            $table->time('waktu_pelaksanaan');
            $table->dateTime('tanggal_buka_pendaftaran');
            $table->dateTime('batas_pendaftaran');
            $table->boolean('status_buka')->default(false);   // PIC membuka/menutup pendaftaran
            $table->boolean('dipublikasi')->default(false);   // Admin mempublikasikan ke katalog
            $table->timestamps();

            $table->foreign('id_pic')->references('id_pic')->on('pic')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kerja');
    }
};
