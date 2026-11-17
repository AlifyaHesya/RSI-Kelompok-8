<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** NotifikasiService pada class diagram, dijadikan tabel agar notifikasi bisa dibaca di aplikasi. */
class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $primaryKey = 'id_notifikasi';

    protected $fillable = ['id_penerima', 'pesan', 'sudah_dibaca'];

    protected $casts = ['sudah_dibaca' => 'boolean'];

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penerima');
    }

    /** buatNotificationForAdmin(): kirim ke semua Super Admin. Mengembalikan false bila tidak ada admin. */
    public static function buatNotificationForAdmin(string $pesan): bool
    {
        $admins = User::where('role', Role::Admin->value)->pluck('id');

        foreach ($admins as $idAdmin) {
            static::create(['id_penerima' => $idAdmin, 'pesan' => $pesan]);
        }

        return $admins->isNotEmpty();
    }
}
