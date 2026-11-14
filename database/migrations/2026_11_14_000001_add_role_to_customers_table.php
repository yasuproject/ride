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
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'role')) {
                $table->string('role', 32)->default('passenger')->after('password');
                $table->index('role');
            }
        });

        if (Schema::hasColumn('customers', 'role')) {
            \Illuminate\Support\Facades\DB::table('customers')
                ->whereNull('role')
                ->orWhere('role', '')
                ->update(['role' => 'passenger']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'role')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
