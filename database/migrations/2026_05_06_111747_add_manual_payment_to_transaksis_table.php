<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // bukti_pembayaran untuk transfer manual
            $table->string('bukti_pembayaran')->nullable()->after('metode_pembayaran');
            // Catatan admin jika ditolak
            $table->text('catatan_admin')->nullable()->after('bukti_pembayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['bukti_pembayaran', 'catatan_admin']);
        });
    }
};
