<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 32)->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        // Move existing mobile-registered (non-admin) users into app_users and
        // repoint their Sanctum tokens so active sessions remain valid.
        $now = now();

        $appUsers = DB::table('users')->where('is_admin', false)->orderBy('id')->get();
        $idMap = [];

        foreach ($appUsers as $user) {
            $newId = DB::table('app_users')->insertGetId([
                'name' => $user->name ?? 'Passenger',
                'phone' => $user->phone,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'created_at' => $user->created_at ?? $now,
                'updated_at' => $user->updated_at ?? $now,
            ]);
            $idMap[$user->id] = $newId;
        }

        if ($idMap !== []) {
            $oldType = 'App\\Models\\User';
            $newType = 'App\\Models\\AppUser';

            foreach ($idMap as $oldId => $newId) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', $oldType)
                    ->where('tokenable_id', $oldId)
                    ->update([
                        'tokenable_type' => $newType,
                        'tokenable_id' => $newId,
                    ]);
            }

            DB::table('users')->whereIn('id', array_keys($idMap))->delete();
        }

        // Admin/dashboard users remain in `users`. Phone is no longer required there.
        if (Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
                $table->dropColumn('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 32)->nullable()->unique()->after('email');
            });
        }

        $appUsers = Schema::hasTable('app_users') ? DB::table('app_users')->orderBy('id')->get() : collect();
        $idMap = [];
        $now = now();

        foreach ($appUsers as $user) {
            $newId = DB::table('users')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'is_admin' => false,
                'remember_token' => null,
                'created_at' => $user->created_at ?? $now,
                'updated_at' => $user->updated_at ?? $now,
            ]);
            $idMap[$user->id] = $newId;
        }

        if ($idMap !== []) {
            $oldType = 'App\\Models\\AppUser';
            $newType = 'App\\Models\\User';
            foreach ($idMap as $oldId => $newId) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', $oldType)
                    ->where('tokenable_id', $oldId)
                    ->update([
                        'tokenable_type' => $newType,
                        'tokenable_id' => $newId,
                    ]);
            }
        }

        Schema::dropIfExists('app_users');
    }
};
