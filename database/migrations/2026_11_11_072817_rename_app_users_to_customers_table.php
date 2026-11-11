<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('app_users') && ! Schema::hasTable('customers')) {
            Schema::rename('app_users', 'customers');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers') && ! Schema::hasTable('app_users')) {
            Schema::rename('customers', 'app_users');
        }
    }
};
