<?php

namespace App\Models;

use App\Enums\KategoriProker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class ProgramKerja extends Model
{
    protected $table = 'program_kerja';

    protected $primaryKey = 'id_proker';

    protected $fillable = [
        'id_pic', 'nama_proker', 'deskripsi', 'kuota', 'lokasi', 'biro', 'kategori',
        'tanggal_pelaksanaan', 'waktu_pelaksanaan', 'tanggal_buka_pendaftaran',
        'batas_pendaftaran', 'status_buka', 'dipublikasi',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriProker::class,
            'tanggal_pelaksanaan' => 'date',
            'tanggal_buka_pendaftaran' => 'datetime',
            'batas_pendaftaran' => 'datetime',
            'status_buka' => 'boolean',
            'dipublikasi' => 'boolean',
        ];
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Pic::class, 'id_pic', 'id_pic');
    }

    public function pengajuan(): HasOne
    {
        return $this->hasOne(Pengajuan::class, 'id_proker', 'id_proker');
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class, 'id_proker', 'id_proker');
    }

    public function pengingat(): HasMany
    {
        return $this->hasMany(Pengingat::class, 'id_proker', 'id_proker');
    }

    public function laporan(): HasOne
    {
        return $this->hasOne(LaporanKegiatan::class, 'id_proker', 'id_proker');
    }

    public function scopeDipublikasi(Builder $query): Builder
    {
        return $query->where('dipublikasi', true);
    }

    /** Waktu mulai pelaksanaan (tanggal + jam) dalam satu objek Carbon. */
    public function waktuMulai(): Carbon
    {
        return $this->tanggal_pelaksanaan->copy()->setTimeFromTimeString($this->waktu_pelaksanaan);
    }

    /**
     * Sequence langkah 3: cekStatusPendaftaran(idProker): statusBuka.
     * Objek ini sudah mewakili idProker. Terbuka jika dipublikasi, dibuka PIC, dan dalam rentang waktu.
     */
    public function cekStatusPendaftaran(): bool
    {
        return $this->dipublikasi
            && $this->status_buka
            && now()->between($this->tanggal_buka_pendaftaran, $this->batas_pendaftaran);
    }

    /** Sequence langkah 5: cekKuotaTersedia(idProker): kuotaTersedia. Dihitung dari jumlah pendaftar. */
    public function cekKuotaTersedia(): bool
    {
        return $this->pendaftaran()->count() < $this->kuota;
    }
}
