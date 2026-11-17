<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h4 { margin-bottom: 2px; }
        p { margin-top: 2px; white-space: pre-line; }
    </style>
</head>
<body>
    <h3>Laporan Kegiatan: {{ $laporan->programKerja->nama_proker }}</h3>
    <p>
        Tanggal realisasi: {{ $laporan->tanggal_realisasi?->format('d-m-Y') ?? '-' }}<br>
        Jumlah peserta hadir: {{ $laporan->jumlah_peserta_hadir ?? '-' }}<br>
        Anggaran realisasi: {{ $laporan->anggaran_realisasi !== null ? 'Rp '.number_format($laporan->anggaran_realisasi, 0, ',', '.') : '-' }}
    </p>

    <h4>Ringkasan Kegiatan</h4>
    <p>{{ $laporan->ringkasan_kegiatan }}</p>

    <h4>Kendala</h4>
    <p>{{ $laporan->kendala ?: '-' }}</p>

    <h4>Evaluasi</h4>
    <p>{{ $laporan->evaluasi ?: '-' }}</p>

    <p>Dibuat: {{ $laporan->tanggal_dibuat->format('d-m-Y H:i') }}</p>
</body>
</html>
