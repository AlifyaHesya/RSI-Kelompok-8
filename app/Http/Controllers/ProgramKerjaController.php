<?php

namespace App\Http\Controllers;

use App\Enums\KategoriProker;
use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use App\Models\ProgramKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

/** UC-09 Mengelola Program Kerja (PIC). */
class ProgramKerjaController extends Controller
{
    public function index()
    {
        $programKerja = auth()->user()->pic
            ->programKerja()
            ->with('pengajuan')
            ->latest()
            ->get();

        return view('pic.proker.index', compact('programKerja'));
    }

    /** getFormulirData() */
    public function create()
    {
        return view('pic.proker.form', [
            'programKerja' => new ProgramKerja,
            'kategori' => KategoriProker::cases(),
        ]);
    }

    /** validasiFormulir() + simpanProgramKerja(): proker baru otomatis berstatus Draft. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(baru: true));
        $pic = auth()->user()->pic;

        $proker = DB::transaction(function () use ($data, $pic) {
            $proker = ProgramKerja::create($data + [
                'id_pic' => $pic->id_pic,
                'status_buka' => false,
                'dipublikasi' => false,
            ]);

            Pengajuan::create([
                'id_pic' => $pic->id_pic,
                'id_proker' => $proker->id_proker,
                'nama_proker' => $proker->nama_proker,
                'status_pengajuan' => StatusPengajuan::Draft,
            ]);

            return $proker;
        });

        return redirect()->route('pic.pengajuan.show', $proker->pengajuan)
            ->with('success', 'Program kerja berhasil disimpan sebagai draft');
    }

    public function edit(ProgramKerja $programKerja)
    {
        $this->pastikanMilik($programKerja);

        return view('pic.proker.form', [
            'programKerja' => $programKerja,
            'kategori' => KategoriProker::cases(),
        ]);
    }

    public function update(Request $request, ProgramKerja $programKerja): RedirectResponse
    {
        $this->pastikanMilik($programKerja);

        if (! $programKerja->pengajuan->bisaDiubahPic()) {
            return back()->with('error', 'Program kerja yang sedang diverifikasi atau sudah disetujui tidak dapat diubah');
        }

        $data = $request->validate($this->rules(baru: false));

        DB::transaction(function () use ($programKerja, $data) {
            $programKerja->update($data);
            $programKerja->pengajuan->update(['nama_proker' => $data['nama_proker']]);
        });

        return redirect()->route('pic.proker.index')->with('success', 'Program kerja berhasil diperbarui');
    }

    /** PIC membuka/menutup pendaftaran pada program kerja yang sudah disetujui. */
    public function toggleBuka(ProgramKerja $programKerja): RedirectResponse
    {
        $this->pastikanMilik($programKerja);

        if ($programKerja->pengajuan->status_pengajuan !== StatusPengajuan::Disetujui) {
            return back()->with('error', 'Pendaftaran hanya dapat dibuka setelah pengajuan disetujui');
        }

        $programKerja->update(['status_buka' => ! $programKerja->status_buka]);

        return back()->with('success', $programKerja->status_buka ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup');
    }

    private function rules(bool $baru): array
    {
        return [
            'nama_proker' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'kuota' => ['required', 'integer', 'min:1'],
            'lokasi' => ['required', 'string', 'max:100'],
            'biro' => ['required', 'string', 'max:100'],
            'kategori' => ['required', new Enum(KategoriProker::class)],
            'tanggal_pelaksanaan' => array_filter(['required', 'date', $baru ? 'after_or_equal:today' : null]),
            'waktu_pelaksanaan' => ['required', 'date_format:H:i'],
            'tanggal_buka_pendaftaran' => ['required', 'date'],
            'batas_pendaftaran' => ['required', 'date', 'after:tanggal_buka_pendaftaran'],
        ];
    }

    private function pastikanMilik(ProgramKerja $programKerja): void
    {
        abort_unless((int) $programKerja->id_pic === (int) auth()->user()->pic->id_pic, 403);
    }
}
