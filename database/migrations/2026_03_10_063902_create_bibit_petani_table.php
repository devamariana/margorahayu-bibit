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
        Schema::create('bibit_petani', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bibit_id')->constrained('bibits')->onDelete('cascade');
            $table->foreignId('petani_id')->constrained('petanis')->onDelete('cascade');
            $table->integer('kuota_maksimal')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bibit_petani');
    }
};
