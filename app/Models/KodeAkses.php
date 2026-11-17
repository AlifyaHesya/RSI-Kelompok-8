<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeAkses extends Model
{
    protected $table = 'kode_akses';

    protected $fillable = ['role', 'kode', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];
}
