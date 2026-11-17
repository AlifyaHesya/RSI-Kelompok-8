<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKegiatan extends Model
{
    protected $table = 'laporan_kegiatan';

    protected $primaryKey = 'id_laporan';

    protected $fillable = [
        'id_proker', 'ringkasan_kegiatan', 'kendala', 'evaluasi', 'anggaran_realisasi',
        'jumlah_peserta_hadir', 'tanggal_realisasi', 'tanggal_dibuat', 'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'anggaran_realisasi' => 'decimal:2',
            'tanggal_realisasi' => 'date',
            'tanggal_dibuat' => 'datetime',
            'tanggal_publikasi' => 'datetime',
        ];
    }

    public function programKerja(): BelongsTo
    {
        return $this->belongsTo(ProgramKerja::class, 'id_proker', 'id_proker');
    }
}
