<?php

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Models\Pengingat;
use Illuminate\Console\Command;

/** jadwalkanPengingat(): dijalankan scheduler tiap menit, mengirim pengingat yang sudah jatuh tempo. */
class KirimPengingat extends Command
{
    protected $signature = 'pengingat:kirim';

    protected $description = 'Kirim notifikasi pengingat program kerja yang sudah jatuh tempo';

    public function handle(): int
    {
        $jatuhTempo = Pengingat::with(['programKerja', 'mahasiswa'])
            ->where('status_pengingat', true)
            ->whereNull('terkirim_pada')
            ->where('waktu_pengingat', '<=', now())
            ->get();

        foreach ($jatuhTempo as $pengingat) {
            $proker = $pengingat->programKerja;

            Notifikasi::create([
                'id_penerima' => $pengingat->mahasiswa->user_id,
                'pesan' => "Pengingat: \"{$proker->nama_proker}\" dimulai pada "
                    .$proker->waktuMulai()->translatedFormat('d F Y H:i').' di '.$proker->lokasi.'.',
            ]);

            $pengingat->update(['terkirim_pada' => now()]);
        }

        $this->info($jatuhTempo->count().' pengingat dikirim.');

        return self::SUCCESS;
    }
}
