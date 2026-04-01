<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->string('log_code', 30)->unique()->nullable()->after('id');
            $table->string('credential_type')->after('log_code');
            $table->string('credential_value')->after('credential_type');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropUnique(['log_code']);
            $table->dropColumn(['log_code', 'credential_type', 'credential_value', 'deleted_at']);
        });
    }
};
