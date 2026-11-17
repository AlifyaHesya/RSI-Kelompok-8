<?php

namespace App\Enums;

// Sesuaikan daftar kategori dengan kebutuhan BEM Anda.
enum KategoriProker: string
{
    case Lomba = 'Lomba';
    case Seminar = 'Seminar';
    case Workshop = 'Workshop';
    case Pengabdian = 'Pengabdian';
    case Lainnya = 'Lainnya';
}
