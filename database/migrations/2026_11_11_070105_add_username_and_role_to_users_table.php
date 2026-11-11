<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role', 32)->default('administrator')->after('username');
            $table->string('email')->nullable()->change();
        });

        $users = DB::table('users')->orderBy('id')->get();
        foreach ($users as $user) {
            $username = null;
            if (! empty($user->email) && str_contains((string) $user->email, '@')) {
                $username = Str::of((string) $user->email)->before('@')->lower()->slug('_')->toString();
            }
            if ($username === '' || $username === null) {
                $username = 'admin_'.$user->id;
            }

            $base = $username;
            $i = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base.'_'.$i;
                $i++;
            }

            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
                'role' => $user->is_admin ? 'administrator' : ($user->role ?? 'administrator'),
                'is_admin' => true,
            ]);
        }

        // Ensure default admin login as requested: username "admin", password "admin".
        if (! app()->environment('testing')) {
            $existing = DB::table('users')->where('username', 'admin')->first();
            $now = now();
            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update([
                    'name' => $existing->name ?: 'Administrator',
                    'role' => $existing->role ?: 'administrator',
                    'is_admin' => true,
                    'password' => Hash::make('admin'),
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->insert([
                    'name' => 'Administrator',
                    'username' => 'admin',
                    'email' => null,
                    'role' => 'administrator',
                    'is_admin' => true,
                    'email_verified_at' => $now,
                    'password' => Hash::make('admin'),
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role']);
        });
    }
};
