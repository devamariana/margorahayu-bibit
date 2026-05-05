<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Bibit;
use App\Models\User;
use App\Notifications\SistemNotifikasi;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function midtransCallback(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        
        // Hashing the received payload for security validation
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if($hashed !== $request->signature_key) {
            Log::warning('Midtrans Webhook: Invalid Signature', ['order_id' => $request->order_id]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transaksi = Transaksi::where('order_id', $request->order_id)->first();
        if (!$transaksi) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transactionStatus = $request->transaction_status;
        
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            // Lunas
            if ($transaksi->status_pembayaran != 'sukses') {
                $transaksi->status_pembayaran = 'sukses';
                $transaksi->save();

                // Notifikasi ke Admin
                $admins = User::where('role', 'admin')->get();
                $bibitNama = $transaksi->bibit->nama_bibit ?? 'Bibit';
                
                foreach ($admins as $admin) {
                    $admin->notify(new SistemNotifikasi(
                        'Pembayaran Otomatis Lunas! ✅', 
                        "Petani #{$transaksi->petani_id} telah melunasi pembayaran untuk bibit '{$bibitNama}' sebesar Rp " . number_format($transaksi->total_harga, 0, ',', '.') . " secara online.", 
                        'success',
                        url('/admin/riwayat-transaksi'),
                        $transaksi->id
                    ));
                }
            }
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            // Gagal / Expired
            if ($transaksi->status_pembayaran != 'batal' && $transaksi->status_pembayaran != 'kadaluarsa') {
                $transaksi->status_pembayaran = 'kadaluarsa';
                $transaksi->save();

                // Kembalikan Stok
                if ($transaksi->bibit) {
                    $transaksi->bibit->stok += $transaksi->jumlah_beli;
                    $transaksi->bibit->save();
                }
            }
        } elseif ($transactionStatus == 'pending') {
            // Pending
            $transaksi->status_pembayaran = 'menunggu_pembayaran';
            $transaksi->save();
        }

        Log::info('Midtrans Webhook success processing: ' . $transactionStatus . ' for OrderID ' . $request->order_id);
        return response()->json(['status' => 'success']);
    }
}
