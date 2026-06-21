<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Periode;
use Carbon\Carbon;

class NormalizePeriodeCommand extends Command
{
    protected $signature = 'periode:normalize {--force : Run without confirmation}';
    protected $description = 'Normalize existing Periode records into 15-day blocks and duplicate bibit sisa ke periode pertama.';

    public function handle()
    {
        $periodes = Periode::all();

        $targets = $periodes->filter(function($p) {
            $start = Carbon::parse($p->tanggal_mulai);
            $end = Carbon::parse($p->tanggal_selesai);
            return $end->diffInDays($start) > 14;
        });

        if ($targets->isEmpty()) {
            $this->info('Tidak ada Periode yang perlu dinormalisasi.');
            return 0;
        }

        $this->info('Ditemukan ' . $targets->count() . ' Periode panjang yang akan dipecah.');

        if (!$this->option('force')) {
            if (!$this->confirm('Lanjutkan memecah dan memodifikasi data Periode? Ini akan membuat Periode baru dan menghapus Periode lama.')) {
                $this->info('Dibatalkan oleh pengguna.');
                return 1;
            }
        }

        foreach ($targets as $p) {
            $this->line('Memproses Periode ID ' . $p->id . ' (' . $p->tanggal_mulai . ' - ' . $p->tanggal_selesai . ')');

            $start = Carbon::parse($p->tanggal_mulai)->startOfDay();
            $origEnd = Carbon::parse($p->tanggal_selesai)->startOfDay();

            $bibits = $p->bibits()->get();

            $chunks = [];
            $cursor = $start->copy();
            while ($cursor->lte($origEnd)) {
                $chunkStart = $cursor->copy();
                $chunkEnd = $chunkStart->copy()->addDays(14);
                if ($chunkEnd->gt($origEnd)) {
                    $chunkEnd = $origEnd->copy();
                }
                $chunks[] = ['start' => $chunkStart->toDateString(), 'end' => $chunkEnd->toDateString()];
                $cursor = $chunkEnd->copy()->addDay();
            }

            // Create new Periode chunks
            foreach ($chunks as $i => $chunk) {
                $data = [
                    'tahun' => Carbon::parse($chunk['start'])->format('Y'),
                    'musim' => $p->musim,
                    'tanggal_mulai' => $chunk['start'],
                    'tanggal_selesai' => $chunk['end'],
                    'status' => ($i === 0 && $p->status === 'aktif') ? 'aktif' : 'berakhir',
                ];

                $newP = Periode::create($data);

                // Duplicate bibits: carry over stok only to first chunk, others get stok 0
                foreach ($bibits as $bibit) {
                    $newBib = $bibit->replicate();
                    $newBib->periode_id = $newP->id;
                    $newBib->is_buka = false;
                    $newBib->tanggal_buka = null;
                    if ($i === 0) {
                        $newBib->stok_awal = $bibit->stok;
                        $newBib->stok = $bibit->stok;
                    } else {
                        $newBib->stok_awal = 0;
                        $newBib->stok = 0;
                    }
                    $newBib->save();
                }
            }

            // After chunks created, delete original periode and its bibits
            foreach ($bibits as $bibit) {
                $bibit->delete();
            }
            $p->delete();

            $this->info('Periode ID ' . $p->id . ' dipecah menjadi ' . count($chunks) . ' blok 15-hari.');
        }

        $this->info('Normalisasi Periode selesai.');
        return 0;
    }
}
