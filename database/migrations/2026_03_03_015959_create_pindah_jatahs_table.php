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
        Schema::create('pindah_jatahs', function (Blueprint $table) {
            $table->id();
            // ID Petani yang memberi jatah
            $table->unsignedBigInteger('pengirim_id');
            // ID Petani yang menerima jatah
            $table->unsignedBigInteger('penerima_id');
            
            $table->integer('jumlah_kg');
            $table->string('alasan')->nullable();
            $table->timestamps();

            // Relasi ke tabel petanis
            $table->foreign('pengirim_id')->references('id')->on('petanis')->onDelete('cascade');
            $table->foreign('penerima_id')->references('id')->on('petanis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pindah_jatahs');
    }
};
