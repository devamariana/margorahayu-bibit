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
        Schema::table('pindah_jatahs', function (Blueprint $table) {
            $table->foreignId('bibit_id')->nullable()->constrained('bibits')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pindah_jatahs', function (Blueprint $table) {
            $table->dropForeign(['bibit_id']);
            $table->dropColumn('bibit_id');
        });
    }
};
