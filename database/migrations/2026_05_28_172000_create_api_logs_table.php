<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_key_id')->nullable(); // Link to API Key
            $table->string('method', 10);                        // GET, POST, PUT, DELETE
            $table->string('path', 255);                         // api/v1/invoices
            $table->string('ip_address', 45)->nullable();
            $table->longText('request_body')->nullable();        // JSON request payload
            $table->integer('response_status');                  // 200, 201, 422, etc.
            $table->longText('response_body')->nullable();       // JSON response payload
            $table->unsignedInteger('duration_ms')->default(0);  // Response time in ms
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('cascade');
            $table->index('api_key_id');
            $table->index('response_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
