<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $transaksi->order_id }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #2D6A4F; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1B4332; margin-bottom: 5px; }
        .title { font-size: 18px; color: #555; text-transform: uppercase; letter-spacing: 2px; }
        .info-box { width: 100%; margin-bottom: 30px; }
        .info-box td { vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th { background-color: #2D6A4F; color: white; padding: 10px; text-align: left; }
        .table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">MARGO RAHAYU II</div>
        <div class="title">{{ $transaksi->status_pembayaran == 'sukses' ? 'Tanda Terima Pembayaran' : 'Bukti Pemesanan Bibit' }}</div>
    </div>
    
    <table class="info-box">
        <tr>
            <td width="50%">
                <strong>Dibayarkan Oleh:</strong><br>
                {{ $petani->nama_lengkap }}<br>
                NIK: {{ $petani->nik }}<br>
                No. WA: {{ $petani->no_hp }}
            </td>
            <td width="50%" style="text-align: right;">
                <strong>Detail Transaksi:</strong><br>
                #{{ $transaksi->order_id }}<br>
                {{ $transaksi->created_at->format('d F Y, H:i') }} WIB<br>
                <strong>Status: {{ $transaksi->status_pembayaran == 'sukses' ? 'LUNAS' : strtoupper(str_replace('_', ' ', $transaksi->status_pembayaran)) }}</strong>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Deskripsi Item</th>
                <th>Lahan Tujuan</th>
                <th style="text-align: center;">Kuantitas</th>
                <th style="text-align: right;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transaksi->bibit->nama_bibit ?? '-' }}</td>
                <td>{{ $transaksi->lahan->nama_blok ?? '-' }}</td>
                <td style="text-align: center;">{{ $transaksi->jumlah_beli }} Kg</td>
                <td style="text-align: right;">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL PEMBAYARAN</td>
                <td style="text-align: right; font-size: 16px;">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Harap cetak atau simpan dokumen ini sebagai bukti pengambilan bibit.<br>
        Dokumen ini diterbitkan oleh Sistem Informasi Margo Rahayu II secara otomatis.
    </div>
</body>
</html>
