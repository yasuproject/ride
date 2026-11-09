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
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('passenger_name');
            $table->string('passenger_phone', 32);
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->string('status')->default('requested'); // requested, assigned, in_progress, completed, cancelled
            $table->decimal('fare', 10, 2)->nullable();
            $table->string('driver_name')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
