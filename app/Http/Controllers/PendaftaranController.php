<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\ProgramKerja;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * UC-06 Mendaftar Program Kerja, mengikuti sequence diagram.
 * Atribut private (mahasiswa, pendaftaran, programKerja) mengikuti class diagram.
 *
 * Method tampilkan...() di bawah adalah penghubung ke kelas boundary (view Blade):
 *   tampilkanFormPendaftaran() -> view pendaftaran.form          (FormPendaftaran)
 *   tampilkanPesan()           -> katalog.show + flash 'pesan'    (HalamanDetailProgramKerja)
 *   tampilkanPesanError()      -> form + $errors                  (FormPendaftaran)
 *   tampilkanKonfirmasi()      -> view pendaftaran.konfirmasi     (HalamanKonfirmasiPendaftaran)
 */
class PendaftaranController extends Controller
{
    private Mahasiswa $mahasiswa;

    private Pendaftaran $pendaftaran;

    private ProgramKerja $programKerja;

    /** Riwayat pendaftaran milik mahasiswa yang login. */
    public function index()
    {
        $pendaftaran = auth()->user()->mahasiswa
            ->pendaftaran()
            ->with('programKerja')
            ->latest('tanggal_pendaftaran')
            ->get();

        return view('pendaftaran.index', compact('pendaftaran'));
    }

    /**
     * Langkah 1–2: mahasiswa menekan "Daftar" di HalamanDetailProgramKerja (pilihDaftar),
     * lalu periksaPendaftaran() dijalankan.
     */
    public function periksaPendaftaran(ProgramKerja $programKerja): View|RedirectResponse
    {
        $this->programKerja = $programKerja;
        $this->mahasiswa = auth()->user()->mahasiswa;

        $idProker = $this->programKerja->id_proker;

        $statusBuka = $this->programKerja->cekStatusPendaftaran();                                         // 3
        $statusPendaftaran = Pendaftaran::cekRiwayatPendaftaran($idProker, $this->mahasiswa->id_mahasiswa); // 4
        $kuotaTersedia = $this->programKerja->cekKuotaTersedia();                                           // 5

        // alt [statusPendaftaran=false, kuotaTersedia=true, statusBuka=true]
        if (! $statusPendaftaran && $kuotaTersedia && $statusBuka) {
            return $this->tampilkanFormPendaftaran();                                                       // 6
        }

        // else
        return $this->tampilkanPesan($this->tentukanAlasan($statusBuka, $statusPendaftaran, $kuotaTersedia)); // 13
    }

    /**
     * Langkah 8: FormPendaftaran mengirim data (kirimDataPendaftaran), lalu
     * validasi (9), simpan (10), dan konfirmasi (11) atau pesan error (12).
     */
    public function simpanPendaftaran(Request $request, ProgramKerja $programKerja): RedirectResponse
    {
        $this->programKerja = $programKerja;
        $this->mahasiswa = auth()->user()->mahasiswa;

        $dataPendaftaran = $request->only(['nama', 'nim', 'email', 'program_studi']) + [
            'id_mahasiswa' => $this->mahasiswa->id_mahasiswa,
            'id_proker' => $this->programKerja->id_proker,
        ];

        $hasilValidasi = $this->validasiDataPendaftaran($dataPendaftaran);                                  // 9

        // alt [hasilValidasi=true] / [else]
        if (! $hasilValidasi) {
            return $this->tampilkanPesanError();                                                            // 12
        }

        $this->pendaftaran = new Pendaftaran;
        $statusSimpan = $this->pendaftaran->simpanPendaftaran($dataPendaftaran);                            // 10

        return $this->tampilkanKonfirmasi($statusSimpan);                                                   // 11
    }

    /** Halaman konfirmasi setelah pendaftaran tersimpan. */
    public function konfirmasi(Pendaftaran $pendaftaran): View
    {
        abort_unless(
            (int) $pendaftaran->id_mahasiswa === (int) auth()->user()->mahasiswa->id_mahasiswa,
            403
        );

        return view('pendaftaran.konfirmasi', [
            'statusSimpan' => true,
            'pendaftaran' => $pendaftaran->load('programKerja'),
        ]);
    }

    /** validasiDataPendaftaran(): meneruskan ke Mahasiswa::validasiDataMahasiswa() (langkah 9). */
    private function validasiDataPendaftaran(array $dataPendaftaran): bool
    {
        return $this->mahasiswa->validasiDataMahasiswa($dataPendaftaran);
    }

    /** Alasan penolakan; urutan pemeriksaan mengikuti pseudo-code periksaPendaftaran(). */
    private function tentukanAlasan(bool $statusBuka, bool $statusPendaftaran, bool $kuotaTersedia): string
    {
        if (! $statusBuka) {
            return 'Pendaftaran sudah ditutup';
        }

        if (! $kuotaTersedia) {
            return 'Kuota pendaftaran sudah penuh';
        }

        return 'Anda sudah terdaftar pada program kerja ini';
    }

    /** FormPendaftaran::tampilkanFormPendaftaran(idProker) */
    private function tampilkanFormPendaftaran(): View
    {
        return view('pendaftaran.form', [
            'programKerja' => $this->programKerja,
            'mahasiswa' => $this->mahasiswa,
        ]);
    }

    /** HalamanDetailProgramKerja::tampilkanPesan(alasan) */
    private function tampilkanPesan(string $alasan): RedirectResponse
    {
        return redirect()->route('katalog.show', $this->programKerja)->with('pesan', $alasan);
    }

    /** FormPendaftaran::tampilkanPesanError(hasilValidasi) */
    private function tampilkanPesanError(): RedirectResponse
    {
        return back()->withErrors($this->mahasiswa->kesalahanValidasi)->withInput();
    }

    /**
     * HalamanKonfirmasiPendaftaran::tampilkanKonfirmasi(statusSimpan).
     * Jika gagal disimpan, form menampilkan pesan error (sesuai pseudo-code PC-06-03).
     */
    private function tampilkanKonfirmasi(bool $statusSimpan): RedirectResponse
    {
        if ($statusSimpan) {
            return redirect()->route('pendaftaran.konfirmasi', $this->pendaftaran);
        }

        return back()
            ->withErrors(['simpan' => 'Pendaftaran gagal disimpan. Pendaftaran mungkin sudah ditutup atau kuota penuh.'])
            ->withInput();
    }
}
