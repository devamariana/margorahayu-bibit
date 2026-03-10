<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petanis', function (Blueprint $table) {
            $table->id();
            // BARIS INI WAJIB ADA:
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('nama_lengkap');
            $table->string('no_hp');
            $table->string('nik')->default('-');
            $table->text('alamat')->nullable();
            $table->decimal('luas_lahan', 10, 2)->default(0);
            $table->enum('status', ['pending', 'aktif', 'nonaktif'])->default('pending');
            $table->string('foto_ktp')->nullable();
            $table->string('foto_kk')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petanis');
    }
};