<?php

namespace App\Http\Controllers;

use App\Enums\KategoriProker;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;

/** UC-03 Katalog, UC-04 Cari & Filter, UC-05 Detail, UC-08 Berdasarkan Tanggal. */
class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = ProgramKerja::dipublikasi()->with('pic.user');

        if ($request->filled('q')) {
            $query->where('nama_proker', 'like', '%'.$request->input('q').'%');
        }
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }
        if ($request->filled('biro')) {
            $query->where('biro', $request->input('biro'));
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_pelaksanaan', $request->input('tanggal'));
        }

        return view('katalog.index', [
            'programKerja' => $query->orderBy('tanggal_pelaksanaan')->paginate(12)->withQueryString(),
            'kategori' => KategoriProker::cases(),
        ]);
    }

    /** tampilkanDetailProgramKerja(). Pesan dari PendaftaranController (flash 'pesan') ditampilkan oleh view. */
    public function show(ProgramKerja $programKerja)
    {
        abort_unless($programKerja->dipublikasi, 404);

        return view('katalog.show', ['programKerja' => $programKerja->load('pic.user')]);
    }
}
