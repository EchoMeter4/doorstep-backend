<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('enabled')->default(true)->after('password');
            $table->string('middle_name')->nullable()->after('name')->change();
            $table->string('second_last_name')->nullable()->after('first_last_name')->change();
            $table->softDeletes();
            $table->dropUnique(['email']);
            $table->unique(['email', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['enabled', 'deleted_at']);
            $table->dropUnique(['email', 'deleted_at']);
            $table->unique('email');
            $table->string('middle_name')->nullable(false)->change();
            $table->string('second_last_name')->nullable(false)->change();
        });
    }
};
