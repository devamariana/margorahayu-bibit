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
            $table->integer('harga_subsidi')->default(0);
            $table->string('sumber_pasokan')->nullable();
            $table->string('gambar')->nullable();
            $table->string('status')->default('tersedia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bibits', function (Blueprint $table) {
            $table->dropColumn(['harga_subsidi', 'sumber_pasokan', 'gambar', 'status']);
        });
    }
};
