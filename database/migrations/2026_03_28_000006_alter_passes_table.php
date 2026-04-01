<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passes', function (Blueprint $table) {
            // Drop the existing non-nullable user_id FK
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Add nullable created_by FK
            $table->foreignId('created_by')->nullable()->after('visitor_id')
                ->constrained('users')->nullOnDelete();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('passes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'deleted_at']);

            $table->foreignId('user_id')->after('visitor_id')
                ->constrained()->cascadeOnDelete();
        });
    }
};
