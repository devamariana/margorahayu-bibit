<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - Margo Rahayu II</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; margin: 20px; }
        .kop { text-align: center; border-bottom: 3px double #1B4332; padding-bottom: 20px; margin-bottom: 30px; }
        .kop h1 { margin: 0; color: #1B4332; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .kop p { margin: 5px 0 0; color: #666; font-size: 12px; }
        
        .report-title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; text-decoration: underline; }
        
        .summary-box { margin-bottom: 20px; }
        .summary-box table { width: 40%; border: 1px solid #ddd; }
        .summary-box td { padding: 8px; }
        .summary-label { font-weight: bold; background: #f4f4f4; width: 60%; }
        
        table.main-table { width: 100%; border-collapse: collapse; border: 1px solid #ddd; }
        table.main-table th { background-color: #1B4332; color: #ffffff; padding: 10px; border: 1px solid #ddd; text-transform: uppercase; }
        table.main-table td { padding: 8px; border: 1px solid #ddd; text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left !important; }
        
        .footer { margin-top: 40px; text-align: right; font-size: 12px; }
        .signature { margin-top: 80px; font-weight: bold; text-decoration: underline; }
        
        .page-info { margin-top: 20px; font-style: italic; color: #999; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>MARGO RAHAYU II</h1>
        <p>KELOMPOK TANI & DISTRIBUSI BIBIT UNGGUL</p>
        <p>Email: contact@margorahayu.com | Laporan Sistem Terintegrasi</p>
    </div>

    <div class="report-title">LAPORAN REKAPITULASI PENJUALAN BIBIT</div>

    <div class="summary-box">
        <table>
            <tr>
                <td class="summary-label">Total Dana Masuk</td>
                <td>Rp {{ number_format($danaMasuk, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="summary-label">Total Bibit Terdistribusi</td>
                <td>{{ number_format($totalBibit, 0, ',', '.') }} Kg</td>
            </tr>
            <tr>
                <td class="summary-label">Tanggal Cetak</td>
                <td>{{ date('d-m-Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">No. Order</th>
                <th width="15%">Tgl. Lunas</th>
                <th width="20%">Nama Petani</th>
                <th width="15%">Jenis Bibit</th>
                <th width="10%">Qty (Kg)</th>
                <th width="20%">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksis as $idx => $t)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $t->order_id ?? '-' }}</td>
                <td>{{ $t->updated_at->format('d-m-Y') }}</td>
                <td class="text-left">{{ strtoupper($t->petani->nama_lengkap ?? '-') }}</td>
                <td>{{ $t->bibit->nama_bibit ?? '-' }}</td>
                <td>{{ number_format($t->jumlah_beli) }}</td>
                <td class="text-right">Rp {{ number_format($t->total_harga, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr style="background:#f4f4f4; font-weight:bold;">
                <td colspan="5" class="text-right">GRAND TOTAL</td>
                <td>{{ number_format($totalBibit) }} Kg</td>
                <td class="text-right">Rp {{ number_format($danaMasuk, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Dicetak oleh Sistem Admin, {{ date('d F Y') }}
        <div class="signature">KETUA KELOMPOK TANI</div>
    </div>

    <div class="page-info">
        *Data yang ditampilkan adalah daftar transaksi yang sudah berstatus LUNAS di database.
    </div>
</body>
</html>
