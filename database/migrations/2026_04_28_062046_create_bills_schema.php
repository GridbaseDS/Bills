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
        // Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'editor', 'viewer'])->default('admin');
            $table->string('avatar', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->timestamps();
        });

        // Clients Table
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 200)->nullable()->index();
            $table->string('contact_name', 150);
            $table->string('email', 150)->index();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Republica Dominicana');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recurring Invoices Table
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'quarterly', 'semiannual', 'annual'])->default('monthly');
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_issue_date')->index();
            $table->integer('occurrences_limit')->nullable();
            $table->integer('occurrences_count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->boolean('auto_send')->default(false);
            $table->enum('send_via', ['email', 'whatsapp', 'both'])->default('email')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Recurring Invoice Items Table
        Schema::create('recurring_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_id')->constrained('recurring_invoices')->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->integer('sort_order')->default(0);
        });

        // Invoices Table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->enum('status', ['draft', 'sent', 'viewed', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft')->index();
            $table->date('issue_date');
            $table->date('due_date')->index();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('sent_via', 20)->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('recurring_id')->nullable()->constrained('recurring_invoices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Invoice Items Table
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->integer('sort_order')->default(0);
        });

        // Quotes Table
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 30)->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted'])->default('draft')->index();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('pdf_path', 255)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('sent_via', 20)->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Quote Items Table
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->integer('sort_order')->default(0);
        });

        // Payments Table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check', 'credit_card', 'paypal', 'other'])->default('bank_transfer');
            $table->date('payment_date');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });

        // Activity Log Table
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['invoice', 'quote', 'client', 'recurring', 'payment', 'system']);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action', 100);
            $table->string('description', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at')->useCurrent()->index();

            $table->index(['entity_type', 'entity_id']);
        });

        // Settings Table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_group', 50)->default('general')->index();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('recurring_invoice_items');
        Schema::dropIfExists('recurring_invoices');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('users');
    }
};
