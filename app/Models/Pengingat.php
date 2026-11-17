<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengingat extends Model
{
    protected $table = 'pengingat';

    protected $primaryKey = 'id_pengingat';

    protected $fillable = ['id_mahasiswa', 'id_proker', 'waktu_pengingat', 'status_pengingat', 'terkirim_pada'];

    protected function casts(): array
    {
        return [
            'waktu_pengingat' => 'datetime',
            'terkirim_pada' => 'datetime',
            'status_pengingat' => 'boolean',
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
}
