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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('MkRide');
            $table->boolean('otp_login_enabled')->default(true);
            $table->string('phone_country_code', 8)->default('+251');
            $table->unsignedTinyInteger('otp_length')->default(6);
            $table->unsignedInteger('otp_expiry_seconds')->default(300);
            $table->unsignedInteger('resend_cooldown_seconds')->default(60);
            $table->unsignedTinyInteger('max_verify_attempts')->default(5);
            $table->string('sms_provider')->default('smsethiopia');
            $table->text('sms_api_key')->nullable(); // encrypted at rest via model cast
            $table->string('sms_sender_id')->nullable();
            $table->text('sms_message_template')->nullable(); // e.g. "Your {app_name} code is {code}. Expires in {minutes} min."
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
