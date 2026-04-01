<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->enum('type', ['pedestrian', 'vehicular', 'mixed'])->default('pedestrian')->after('description');
            $table->boolean('enabled')->default(true)->after('type');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['description', 'type', 'enabled', 'deleted_at']);
        });
    }
};
