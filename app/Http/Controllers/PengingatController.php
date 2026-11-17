<?php

namespace App\Http\Controllers;

use App\Models\Pengingat;
use App\Models\ProgramKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** UC-07 Mengaktifkan Notifikasi Pengingat. Pengiriman dilakukan command pengingat:kirim. */
class PengingatController extends Controller
{
    /** aktifkanPengingat() + validasiWaktuPengingat() + simpanPengaturanPengingat(). */
    public function store(Request $request, ProgramKerja $programKerja): RedirectResponse
    {
        abort_unless($programKerja->dipublikasi, 404);

        $data = $request->validate([
            'waktu_pengingat' => ['required', 'date', 'after:now'],
        ]);

        // pengingat harus sebelum program kerja dimulai
        if (\Illuminate\Support\Carbon::parse($data['waktu_pengingat'])->gte($programKerja->waktuMulai())) {
            return back()->withErrors(['waktu_pengingat' => 'Waktu pengingat harus sebelum program kerja dimulai'])
                ->withInput();
        }

        Pengingat::updateOrCreate(
            [
                'id_mahasiswa' => auth()->user()->mahasiswa->id_mahasiswa,
                'id_proker' => $programKerja->id_proker,
            ],
            [
                'waktu_pengingat' => $data['waktu_pengingat'],
                'status_pengingat' => true,
                'terkirim_pada' => null,
            ]
        );

        return back()->with('success', 'Pengingat berhasil diaktifkan');
    }
}
