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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            
            // Supplier/Provider Info
            $table->string('provider_name', 200);
            $table->string('provider_tax_id', 20)->nullable()->index(); // RNC or Cédula
            
            // Fiscal invoice info (DGII 606)
            $table->string('ncf', 13)->nullable()->index(); // NCF / e-NCF
            $table->date('expense_date')->index();
            
            // Amounts
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00); // ITBIS
            $table->decimal('total', 12, 2)->default(0.00);
            
            // DGII 606 Classifications
            $table->string('expense_type', 2)->default('02'); // '01' to '11'
            $table->string('payment_method', 2)->default('02'); // '01' to '07'
            
            $table->text('notes')->nullable();
            
            // Audits
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
