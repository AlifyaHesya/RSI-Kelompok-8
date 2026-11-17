<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';

    protected $primaryKey = 'id_mahasiswa';

    protected $fillable = ['user_id', 'nim', 'program_studi'];

    /** Pesan kesalahan dari validasiDataMahasiswa() terakhir (tidak disimpan ke database). */
    public ?MessageBag $kesalahanValidasi = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class, 'id_mahasiswa', 'id_mahasiswa');
    }

    public function pengingat(): HasMany
    {
        return $this->hasMany(Pengingat::class, 'id_mahasiswa', 'id_mahasiswa');
    }

    /**
     * Sequence langkah 9: validasiDataMahasiswa(dataPendaftaran): hasilValidasi.
     * Memeriksa kelengkapan nama, NIM, email, program studi, dan memastikan NIM sama dengan akun yang login.
     */
    public function validasiDataMahasiswa(array $dataPendaftaran): bool
    {
        $validator = Validator::make($dataPendaftaran, [
            'nama' => ['required', 'string', 'max:100'],
            'nim' => ['required', 'string', Rule::in([$this->nim])],
            'email' => ['required', 'email', 'max:100'],
            'program_studi' => ['required', 'string', 'max:100'],
        ], [
            'required' => 'Data tidak lengkap: :attribute wajib diisi',
            'nim.in' => 'NIM tidak sesuai dengan akun Anda',
        ], [
            'program_studi' => 'program studi',
        ]);

        $hasilValidasi = $validator->passes();
        $this->kesalahanValidasi = $validator->errors();

        return $hasilValidasi;
    }
}
