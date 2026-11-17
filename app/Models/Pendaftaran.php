<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class Pendaftaran extends Model
{
    protected $table = 'pendaftaran';

    protected $primaryKey = 'id_pendaftaran';

    protected $fillable = ['id_mahasiswa', 'id_proker', 'tanggal_pendaftaran', 'status_pendaftaran'];

    protected function casts(): array
    {
        return [
            'tanggal_pendaftaran' => 'datetime',
            'status_pendaftaran' => 'boolean',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa', 'id_mahasiswa');
    }

    public function programKerja(): BelongsTo
    {
        return $this->belongsTo(ProgramKerja::class, 'id_proker', 'id_proker');
    }

    /**
     * Sequence langkah 4: cekRiwayatPendaftaran(idProker, idMahasiswa): statusPendaftaran.
     * true = mahasiswa sudah terdaftar pada proker tersebut.
     */
    public static function cekRiwayatPendaftaran(int $idProker, int $idMahasiswa): bool
    {
        return static::where('id_proker', $idProker)
            ->where('id_mahasiswa', $idMahasiswa)
            ->where('status_pendaftaran', true)
            ->exists();
    }

    /**
     * Sequence langkah 10: simpanPendaftaran(dataPendaftaran): statusSimpan.
     * $dataPendaftaran wajib berisi id_mahasiswa dan id_proker.
     *
     * Status buka, kuota, dan riwayat diperiksa ulang di dalam transaksi dengan kunci baris proker,
     * agar dua pendaftar bersamaan tidak melewati kuota (pemeriksaan di controller bisa sudah usang).
     */
    public function simpanPendaftaran(array $dataPendaftaran): bool
    {
        try {
            return DB::transaction(function () use ($dataPendaftaran) {
                $proker = ProgramKerja::whereKey($dataPendaftaran['id_proker'])->lockForUpdate()->first();

                if (! $proker
                    || ! $proker->cekStatusPendaftaran()
                    || ! $proker->cekKuotaTersedia()
                    || static::cekRiwayatPendaftaran($proker->id_proker, $dataPendaftaran['id_mahasiswa'])) {
                    return false;
                }

                return $this->fill([
                    'id_mahasiswa' => $dataPendaftaran['id_mahasiswa'],
                    'id_proker' => $proker->id_proker,
                    'tanggal_pendaftaran' => now(),
                    'status_pendaftaran' => true,
                ])->save();
            });
        } catch (UniqueConstraintViolationException) {
            return false; // pendaftaran ganda yang lolos pemeriksaan
        }
    }

    /** ambilDataPeserta(): peserta aktif sebuah proker, urut berdasarkan waktu daftar. */
    public static function ambilDataPeserta(int $idProker): Collection
    {
        return static::with('mahasiswa.user')
            ->where('id_proker', $idProker)
            ->where('status_pendaftaran', true)
            ->orderBy('tanggal_pendaftaran')
            ->get();
    }
}
