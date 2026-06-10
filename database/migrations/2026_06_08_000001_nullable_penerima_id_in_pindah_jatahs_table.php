<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE pindah_jatahs DROP FOREIGN KEY pindah_jatahs_penerima_id_foreign');
        DB::statement('ALTER TABLE pindah_jatahs MODIFY penerima_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE pindah_jatahs ADD CONSTRAINT pindah_jatahs_penerima_id_foreign FOREIGN KEY (penerima_id) REFERENCES petanis(id) ON DELETE CASCADE');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE pindah_jatahs DROP FOREIGN KEY pindah_jatahs_penerima_id_foreign');
        DB::statement('ALTER TABLE pindah_jatahs MODIFY penerima_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE pindah_jatahs ADD CONSTRAINT pindah_jatahs_penerima_id_foreign FOREIGN KEY (penerima_id) REFERENCES petanis(id) ON DELETE CASCADE');
        Schema::enableForeignKeyConstraints();
    }
};
