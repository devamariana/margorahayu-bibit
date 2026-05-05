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
        Schema::table('bibits', function (Blueprint $table) {
            $table->integer('stok_awal')->nullable();
            $table->dateTime('tanggal_buka')->nullable();
            $table->decimal('total_luas_snapshot', 15, 2)->nullable();
            $table->boolean('is_buka')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bibits', function (Blueprint $table) {
            $table->dropColumn(['stok_awal', 'tanggal_buka', 'total_luas_snapshot', 'is_buka']);
        });
    }
};
