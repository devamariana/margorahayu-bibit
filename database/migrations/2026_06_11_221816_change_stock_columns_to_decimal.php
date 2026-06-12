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
            $table->decimal('stok', 10, 2)->default(0)->change();
            $table->decimal('stok_awal', 10, 2)->default(0)->change();
        });

        Schema::table('pindah_jatahs', function (Blueprint $table) {
            $table->decimal('jumlah_kg', 10, 2)->default(0)->change();
        });

        Schema::table('transaksis', function (Blueprint $table) {
            $table->decimal('jumlah_beli', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bibits', function (Blueprint $table) {
            $table->integer('stok')->default(0)->change();
            $table->integer('stok_awal')->default(0)->change();
        });

        Schema::table('pindah_jatahs', function (Blueprint $table) {
            $table->integer('jumlah_kg')->default(0)->change();
        });

        Schema::table('transaksis', function (Blueprint $table) {
            $table->integer('jumlah_beli')->default(0)->change();
        });
    }
};
