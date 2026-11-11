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
        Schema::table('app_users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('password');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropIndex(['is_blocked']);
            $table->dropColumn(['is_blocked', 'blocked_at']);
        });
    }
};
