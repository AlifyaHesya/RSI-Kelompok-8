<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h3>Rekap Data Peserta</h3>
    <p>
        Program kerja: <strong>{{ $programKerja->nama_proker }}</strong><br>
        Jumlah peserta: {{ $peserta->count() }}<br>
        Dicetak: {{ now()->format('d-m-Y H:i') }}
    </p>
    <table>
        <thead>
            <tr><th>No</th><th>NIM</th><th>Nama</th><th>Email</th><th>Program Studi</th><th>Tanggal Daftar</th></tr>
        </thead>
        <tbody>
        @foreach ($peserta as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->mahasiswa->nim }}</td>
                <td>{{ $p->mahasiswa->user->name }}</td>
                <td>{{ $p->mahasiswa->user->email }}</td>
                <td>{{ $p->mahasiswa->program_studi }}</td>
                <td>{{ $p->tanggal_pendaftaran->format('d-m-Y H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
