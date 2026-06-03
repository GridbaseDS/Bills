<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dgii_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->string('encf', 20)->nullable()->index();
            $table->string('ecf_type', 5)->nullable();

            // What step in the pipeline this log entry represents
            $table->string('step', 50); // e.g. 'encf_assigned', 'xml_built', 'xml_signed', 'auth_token', 'submit_request', 'submit_response', 'status_check', 'qr_verify'
            $table->enum('level', ['info', 'warning', 'error', 'critical'])->default('info');

            // Core data
            $table->text('message');
            $table->json('context')->nullable(); // Structured data: request headers, response body, XML excerpts, etc.

            // HTTP details (when applicable)
            $table->string('http_method', 10)->nullable();
            $table->text('http_url')->nullable();
            $table->integer('http_status')->nullable();
            $table->text('http_request_body_excerpt')->nullable(); // First 2000 chars of request
            $table->text('http_response_body')->nullable();        // Full response body
            $table->float('http_duration_ms')->nullable();         // Round-trip time

            // Outcome tracking
            $table->string('dgii_track_id', 80)->nullable();
            $table->string('dgii_status', 30)->nullable(); // accepted, rejected, pending, contingency
            $table->text('dgii_error_messages')->nullable();

            // QR verification (post-submit)
            $table->boolean('qr_verified')->nullable();   // true = ConsultaTimbre returned the invoice
            $table->text('qr_url')->nullable();

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dgii_logs');
    }
};
