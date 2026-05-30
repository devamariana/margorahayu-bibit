<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - {{ $transaksi->order_id }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 70mm; 
            margin: 0 auto; 
            padding: 10px; 
            background: #fff;
            color: #000;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .logo { font-size: 16px; font-weight: bold; }
        .address { font-size: 10px; }
        .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        .item { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 5px; }
        .total { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 10px; }
        .footer { text-align: center; font-size: 9px; margin-top: 20px; }
        .qr-placeholder { text-align: center; margin: 10px 0; }
        
        @media print {
            .no-print { display: none; }
            body { width: 100%; padding: 0; }
        }

        .btn-print {
            display: block;
            width: 100%;
            background: #2D6A4F;
            color: white;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <a href="javascript:void(0)" onclick="window.print()" class="btn-print">KLIK UNTUK CETAK STRUK</a>
        <p style="font-size: 10px; text-align: center;">Jika printer thermal tidak muncul, pastikan pengaturan "Paper Size" adalah 58mm atau 80mm.</p>
        <hr>
    </div>

    <div class="header">
        <div class="logo">MARGO RAHAYU II</div>
        <div class="address">Dusun Kademangan, Desa Bendoagung<br>Trenggalek, Jawa Timur</div>
    </div>

    <div class="divider"></div>

    <div style="font-size: 10px;">
        <div>TGL: {{ $transaksi->created_at->format('d/m/Y H:i') }}</div>
        <div>TRX: {{ $transaksi->order_id }}</div>
        <div>PLG: {{ $transaksi->petani?->nama_lengkap ?? 'Tidak diketahui' }}</div>
    </div>

    <div class="divider"></div>

    <div class="item">
        <span>{{ $transaksi->bibit?->nama_bibit ?? 'Bibit' }}</span>
    </div>
    <div class="item">
        <span>{{ $transaksi->jumlah_beli }} Kg x @ Rp {{ number_format($transaksi->bibit?->harga_subsidi ?? 0, 0, ',', '.') }}</span>
        <span>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
    </div>

    <div class="divider"></div>

    <div class="total">
        <span>TOTAL</span>
        <span>Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
    </div>
    
    <div style="font-size: 10px; margin-top: 5px;">
        METODE: {{ strtoupper(str_replace('_', ' ', $transaksi->metode_pembayaran)) }}<br>
        STATUS: {{ strtoupper($transaksi->status_pembayaran) }}
    </div>

    <div class="qr-placeholder">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ $transaksi->order_id }}" width="80" alt="QR">
    </div>

    <div class="footer">
        *** TERIMA KASIH ***<br>
        Bawa struk ini saat pengambilan bibit.<br>
        Sistem Margo Rahayu II
    </div>

</body>
</html>
