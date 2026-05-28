<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // Descriptive name ("Mi Tienda Web")
            $table->string('key', 64)->unique();                 // SHA-256 hashed token
            $table->string('plain_key_prefix', 12);              // First chars for visual identification ("gb_xxxxxxxx")
            $table->json('permissions')->nullable();              // ["invoices.create", "quotes.create", ...]
            $table->unsignedInteger('rate_limit')->default(60);  // Requests per minute
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
