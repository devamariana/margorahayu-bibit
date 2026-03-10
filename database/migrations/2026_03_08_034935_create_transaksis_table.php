<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke Petani
            $table->foreignId('petani_id')->constrained('petanis')->onDelete('cascade');
            
            // PENTING: Menghubungkan ke Lahan spesifik (Revisi: Jatah per Lahan)
            $table->foreignId('lahan_id')->constrained('lahans')->onDelete('cascade');
            
            // Menghubungkan ke Bibit yang dibeli
            $table->foreignId('bibit_id')->constrained('bibits')->onDelete('cascade');
            
            $table->integer('jumlah_beli');    // Dalam Kg
            $table->bigInteger('total_harga'); // Total Rupiah
            $table->string('metode_pembayaran'); // VA atau QRIS
            $table->string('status_pembayaran')->default('pending'); // pending, sukses, batal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};