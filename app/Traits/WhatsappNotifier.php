<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WhatsappNotifier
{
    /**
     * Helper untuk mengirim pesan WhatsApp via Fonnte
     */
    protected function sendWA($target, $message)
    {
        // Prioritas Token dari .env, jika tidak ada pakai default (seperti kodingan awal)
        $fonnteToken = env('FONNTE_TOKEN', '25pu9ReN7AneBKWEyipa');
        
        // Normalisasi Nomor: Pastikan semua jadi format 628...
        $targets = explode(',', $target);
        $normalizedTargets = array_map(function($number) {
            $number = preg_replace('/[^0-9]/', '', trim($number)); // Hapus karakter non-angka
            
            if (str_starts_with($number, '0')) {
                return '62' . substr($number, 1);
            } elseif (str_starts_with($number, '8')) {
                return '62' . $number;
            } elseif (str_starts_with($number, '62')) {
                return $number;
            }
            return $number;
        }, $targets);
        $finalTarget = implode(',', $normalizedTargets);

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $fonnteToken,
            ])->post('https://api.fonnte.com/send', [
                'target' => $finalTarget,
                'message' => $message,
                'delay' => '2'
            ]);

            // Log Hasil untuk Debugging
            \Log::info("Fonnte WA Sent to: " . $finalTarget);
            \Log::info("Fonnte API Response: " . $response->body());

            if ($response->failed()) {
                Log::error("Fonnte API Failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Fonnte WA Error: " . $e->getMessage());
        }
    }
}
