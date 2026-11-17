<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan;
use App\Models\Notifikasi;
use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** UC-10 Mengajukan Program Kerja, UC-11 Melihat Status Pengajuan (PIC). */
class PengajuanController extends Controller
{
    public function index()
    {
        $pengajuan = auth()->user()->pic
            ->pengajuan()
            ->with('programKerja')
            ->latest('updated_at')
            ->get();

        return view('pic.pengajuan.index', compact('pengajuan'));
    }

    public function show(Pengajuan $pengajuan)
    {
        $this->pastikanMilik($pengajuan);

        return view('pic.pengajuan.show', [
            'pengajuan' => $pengajuan->load('programKerja'),
            'dokumenKurang' => $pengajuan->dokumenKurang(),
        ]);
    }

    public function unggahBerkas(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        $this->pastikanMilik($pengajuan);
        abort_unless($pengajuan->bisaDiubahPic(), 403);

        $data = $request->validate([
            'berkas_proposal' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'berkas_rab' => ['nullable', 'file', 'mimes:pdf,xls,xlsx', 'max:5120'],
            'berkas_surat_pengantar' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'link_bukti' => ['nullable', 'url', 'max:255'],
        ]);

        foreach (array_keys(Pengajuan::BERKAS_WAJIB) as $kolom) {
            if ($request->hasFile($kolom)) {
                if ($pengajuan->{$kolom}) {
                    Storage::delete($pengajuan->{$kolom});
                }
                $pengajuan->{$kolom} = $request->file($kolom)->store("pengajuan/{$pengajuan->id_pengajuan}");
            }
        }

        if ($request->filled('link_bukti')) {
            $pengajuan->link_bukti = $data['link_bukti'];
        }

        $pengajuan->berkas_lengkap = $pengajuan->cekBerkasLengkap();
        $pengajuan->save();

        return back()->with('success', 'Berkas berhasil diunggah');
    }

    /**
     * submitPengajuan(): PIC menekan "Ajukan". Jika berkas kurang, tolak dengan petunjuk dokumen;
     * jika lengkap, minta konfirmasi (dialog Konfirmasi/Batal di UI).
     */
    public function submitPengajuan(Pengajuan $pengajuan): RedirectResponse
    {
        $this->pastikanMilik($pengajuan);
        abort_unless($pengajuan->bisaDiubahPic(), 403);

        if (! $pengajuan->cekBerkasLengkap()) {
            return back()
                ->with('error', 'Pengajuan ditolak sistem, berkas belum lengkap')
                ->with('dokumen_kurang', $pengajuan->dokumenKurang());
        }

        return back()->with('perlu_konfirmasi', true);
    }

    /**
     * prosesPengajuan(): PIC menekan "Konfirmasi". Status menjadi "Menunggu Konfirmasi"
     * (sesuai output pseudo-code PC-10-02) lalu Super Admin diberi notifikasi.
     */
    public function prosesPengajuan(Pengajuan $pengajuan): RedirectResponse
    {
        $this->pastikanMilik($pengajuan);
        abort_unless($pengajuan->bisaDiubahPic(), 403);

        if (! $pengajuan->cekBerkasLengkap()) {
            return back()->with('error', 'Pengajuan gagal diproses, berkas belum lengkap');
        }

        DB::transaction(function () use ($pengajuan) {
            $pengajuan->updateStatus(StatusPengajuan::Menunggu);
            Notifikasi::buatNotificationForAdmin(
                "Pengajuan program kerja baru menunggu verifikasi: {$pengajuan->nama_proker}"
            );
        });

        return redirect()->route('pic.pengajuan.index')->with('success', 'Pengajuan berhasil dikirim');
    }

    private function pastikanMilik(Pengajuan $pengajuan): void
    {
        abort_unless((int) $pengajuan->id_pic === (int) auth()->user()->pic->id_pic, 403);
    }
}
