<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Draft = 'Draft';
    case Menunggu = 'Menunggu Konfirmasi';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
}
