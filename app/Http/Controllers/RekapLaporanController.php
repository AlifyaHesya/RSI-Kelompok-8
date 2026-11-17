<?php

namespace App\Http\Controllers;

use App\Models\LaporanKegiatan;
use App\Models\Pendaftaran;
use App\Models\ProgramKerja;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** UC-12 Rekap Data Peserta, UC-13 Laporan Kegiatan (PIC). PDF memakai barryvdh/laravel-dompdf. */
class RekapLaporanController extends Controller
{
    /** getDaftarProgramKerjaPIC() */
    public function index()
    {
        $programKerja = auth()->user()->pic->programKerja()->withCount('pendaftaran')->latest()->get();

        return view('pic.rekap.index', compact('programKerja'));
    }

    /** getOpsiDokumen(): Rekap Data Peserta atau Laporan Kegiatan. */
    public function opsiDokumen(ProgramKerja $programKerja)
    {
        $this->pastikanMilik($programKerja);

        return view('pic.rekap.opsi', compact('programKerja'));
    }

    /** periksaDataPeserta(): bila kosong tampilkan pesan, bila ada tampilkan pilihan format. */
    public function periksaDataPeserta(ProgramKerja $programKerja)
    {
        $this->pastikanMilik($programKerja);

        $peserta = Pendaftaran::ambilDataPeserta($programKerja->id_proker);

        if ($peserta->isEmpty()) {
            return back()->with('error', 'Data peserta kosong');
        }

        return view('pic.rekap.format', ['programKerja' => $programKerja, 'jumlah' => $peserta->count()]);
    }

    /** buatRekapPeserta(idProker, formatBerkas): format = pdf | excel (CSV yang dapat dibuka di Excel). */
    public function buatRekapPeserta(Request $request, ProgramKerja $programKerja)
    {
        $this->pastikanMilik($programKerja);

        $data = $request->validate(['format' => ['required', 'in:pdf,excel']]);
        $peserta = Pendaftaran::ambilDataPeserta($programKerja->id_proker);

        if ($peserta->isEmpty()) {
            return back()->with('error', 'Data peserta kosong, rekap tidak dapat dibuat');
        }

        $namaFile = 'rekap-peserta-'.str($programKerja->nama_proker)->slug();

        if ($data['format'] === 'pdf') {
            return Pdf::loadView('pdf.rekap-peserta', compact('programKerja', 'peserta'))
                ->download($namaFile.'.pdf');
        }

        return $this->unduhCsv($peserta, $namaFile.'.csv');
    }

    public function formLaporan(ProgramKerja $programKerja)
    {
        $this->pastikanMilik($programKerja);

        return view('pic.rekap.laporan', [
            'programKerja' => $programKerja,
            'laporan' => $programKerja->laporan ?? new LaporanKegiatan,
        ]);
    }

    /** validasiDataLaporan() + simpanLaporan(): maksimal satu laporan per program kerja. */
    public function simpanLaporan(Request $request, ProgramKerja $programKerja): RedirectResponse
    {
        $this->pastikanMilik($programKerja);

        $data = $request->validate([
            'ringkasan_kegiatan' => ['required', 'string'],
            'kendala' => ['nullable', 'string'],
            'evaluasi' => ['nullable', 'string'],
            'anggaran_realisasi' => ['nullable', 'numeric', 'min:0'],
            'jumlah_peserta_hadir' => ['nullable', 'integer', 'min:0'],
            'tanggal_realisasi' => ['nullable', 'date'],
        ], ['ringkasan_kegiatan.required' => 'Data laporan belum lengkap']);

        $laporan = LaporanKegiatan::firstOrNew(['id_proker' => $programKerja->id_proker]);
        $laporan->fill($data);
        $laporan->tanggal_dibuat ??= now();
        $laporan->save();

        return redirect()->route('pic.rekap.opsi', $programKerja)->with('success', 'Laporan berhasil disimpan');
    }

    /** unduhLaporan(idLaporan, formatBerkas): saat ini PDF. */
    public function unduhLaporan(LaporanKegiatan $laporan)
    {
        $laporan->load('programKerja');
        $this->pastikanMilik($laporan->programKerja);

        return Pdf::loadView('pdf.laporan', compact('laporan'))
            ->download('laporan-'.str($laporan->programKerja->nama_proker)->slug().'.pdf');
    }

    private function unduhCsv($peserta, string $namaFile): StreamedResponse
    {
        return response()->streamDownload(function () use ($peserta) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($out, ['No', 'NIM', 'Nama', 'Email', 'Program Studi', 'Tanggal Pendaftaran'], ',', '"', '\\');

            foreach ($peserta as $i => $p) {
                fputcsv($out, [
                    $i + 1,
                    $p->mahasiswa->nim,
                    $p->mahasiswa->user->name,
                    $p->mahasiswa->user->email,
                    $p->mahasiswa->program_studi,
                    $p->tanggal_pendaftaran->format('Y-m-d H:i'),
                ], ',', '"', '\\');
            }

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pastikanMilik(ProgramKerja $programKerja): void
    {
        abort_unless((int) $programKerja->id_pic === (int) auth()->user()->pic->id_pic, 403);
    }
}
