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
        Schema::create('user_biometrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_token', 64)->index();
            $table->text('credential_id'); // Base64URL encoded WebAuthn Credential ID
            $table->text('public_key');    // PEM or CBOR formatted public key
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->string('authenticator_name', 100)->default('Biometric Device');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_biometrics');
    }
};
