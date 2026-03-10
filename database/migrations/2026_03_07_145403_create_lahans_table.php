<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lahans', function (Blueprint $table) {
            $table->id();
            // Menghubungkan lahan ke petani (Maria/User lain)
            $table->foreignId('petani_id')->constrained('petanis')->onDelete('cascade');
            
            $table->string('nama_blok');      // Contoh: Sawah Blok Utara
            $table->integer('luas_lahan');    // Luas dalam m2
            $table->string('rencana_bibit');  // Contoh: Padi, Jagung, dll (Revisi Kamu)
            $table->string('jenis_tanah')->nullable();
            $table->string('lokasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lahans');
    }
};