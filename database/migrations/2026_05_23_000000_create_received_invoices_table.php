<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('received_invoices', function (Blueprint $table) {
            $table->id();
            
            // Emisor info (from received e-CF XML)
            $table->string('rnc_emisor', 11);
            $table->string('razon_social_emisor', 250)->nullable();
            $table->string('encf', 13);
            $table->string('ecf_type', 3)->nullable(); // E31, E33, E34, E44, E45...
            
            // Invoice data
            $table->date('fecha_emision');
            $table->decimal('monto_total', 18, 2)->default(0);
            $table->text('raw_xml')->nullable();
            
            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('rejection_reason', 250)->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // ACECF sending status
            $table->boolean('acecf_sent_to_dgii')->default(false);
            $table->boolean('acecf_sent_to_emisor')->default(false);
            $table->text('dgii_acecf_response')->nullable();
            $table->text('emisor_acecf_response')->nullable();
            $table->timestamp('acecf_sent_at')->nullable();
            
            // Unique constraint: one approval per eNCF per emisor
            $table->unique(['rnc_emisor', 'encf']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_invoices');
    }
};
