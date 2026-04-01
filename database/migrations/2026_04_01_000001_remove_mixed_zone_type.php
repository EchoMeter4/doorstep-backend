<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('zones')->where('type', 'mixed')->update(['type' => 'pedestrian']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE zones MODIFY COLUMN type ENUM('pedestrian', 'vehicular') NOT NULL DEFAULT 'pedestrian'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE zones MODIFY COLUMN type ENUM('pedestrian', 'vehicular', 'mixed') NOT NULL DEFAULT 'pedestrian'");
        }
    }
};
