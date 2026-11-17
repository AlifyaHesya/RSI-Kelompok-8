<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan;
use App\Models\Notifikasi;
use App\Models\Pengajuan;
use App\Models\ProgramKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** UC-14 Melihat Pengajuan, UC-15 Menyetujui/Menolak, UC-16 Mengelola Publikasi (Super Admin). */
class AdminPengajuanController extends Controller
{
    public function index(Request $request)
    {
        $pengajuan = Pengajuan::with(['programKerja', 'pic.user'])
            ->where('status_pengajuan', '!=', StatusPengajuan::Draft->value) // draft belum diajukan
            ->when($request->filled('status'), fn ($q) => $q->where('status_pengajuan', $request->input('status')))
            ->orderByRaw('status_pengajuan = ? desc', [StatusPengajuan::Menunggu->value])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pengajuan.index', compact('pengajuan'));
    }

    public function show(Pengajuan $pengajuan)
    {
        abort_if($pengajuan->status_pengajuan === StatusPengajuan::Draft, 404);

        return view('admin.pengajuan.show', ['pengajuan' => $pengajuan->load(['programKerja', 'pic.user'])]);
    }

    public function setujui(Pengajuan $pengajuan): RedirectResponse
    {
        if ($pengajuan->status_pengajuan !== StatusPengajuan::Menunggu) {
            return back()->with('error', 'Hanya pengajuan berstatus Menunggu Konfirmasi yang dapat diproses');
        }

        $pengajuan->updateStatus(StatusPengajuan::Disetujui);
        $this->beriTahuPic($pengajuan, "Pengajuan \"{$pengajuan->nama_proker}\" disetujui.");

        return redirect()->route('admin.pengajuan.index')->with('success', 'Pengajuan disetujui');
    }

    public function tolak(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        if ($pengajuan->status_pengajuan !== StatusPengajuan::Menunggu) {
            return back()->with('error', 'Hanya pengajuan berstatus Menunggu Konfirmasi yang dapat diproses');
        }

        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);

        $pengajuan->updateStatus(StatusPengajuan::Ditolak, $data['catatan']);
        $this->beriTahuPic($pengajuan, "Pengajuan \"{$pengajuan->nama_proker}\" ditolak: {$data['catatan']}");

        return redirect()->route('admin.pengajuan.index')->with('success', 'Pengajuan ditolak');
    }

    /** UC-16: tampilkan/sembunyikan program kerja di katalog mahasiswa. */
    public function publikasi(ProgramKerja $programKerja): RedirectResponse
    {
        if ($programKerja->pengajuan?->status_pengajuan !== StatusPengajuan::Disetujui) {
            return back()->with('error', 'Hanya program kerja yang sudah disetujui yang dapat dipublikasikan');
        }

        $programKerja->update(['dipublikasi' => ! $programKerja->dipublikasi]);

        return back()->with('success', $programKerja->dipublikasi ? 'Program kerja dipublikasikan' : 'Publikasi dibatalkan');
    }

    private function beriTahuPic(Pengajuan $pengajuan, string $pesan): void
    {
        Notifikasi::create([
            'id_penerima' => $pengajuan->pic->user_id,
            'pesan' => $pesan,
        ]);
    }
}
