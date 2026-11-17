<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pic extends Model
{
    protected $table = 'pic';

    protected $primaryKey = 'id_pic';

    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function programKerja(): HasMany
    {
        return $this->hasMany(ProgramKerja::class, 'id_pic', 'id_pic');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'id_pic', 'id_pic');
    }
}
