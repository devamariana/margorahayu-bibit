<!DOCTYPE html>
<html>
<head>
    <title>Laporan Data Petani</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; color: #333; }
        .badge-success { color: green; font-weight: bold; }
        .badge-warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>SISTEM INFORMASI KELOMPOK TANI MARGO RAHAYU II</h2>
        <p>Laporan Keseluruhan Data Petani</p>
        <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>No HP / WA</th>
                <th>NIK</th>
                <th>Luas Lahan (m²)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($petanis as $index => $p)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $p->user->username ?? '-' }}</td>
                <td>{{ $p->nama_lengkap ?? '-' }}</td>
                <td>{{ $p->no_hp ?? '-' }}</td>
                <td>{{ $p->nik ?? '-' }}</td>
                <td>{{ number_format(App\Models\Lahan::where('petani_id', $p->id)->sum('luas_lahan'), 0, ',', '.') }} m²</td>
                <td>
                    @if($p->status == 'disetujui')
                        <span class="badge-success">TERVERIFIKASI</span>
                    @else
                        <span class="badge-warning">PENDING</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
