<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add e-CF configuration fields to recurring_invoices.
     * Allows each recurring subscription to specify whether invoices
     * should be electronic (ECF) and which type (31, 32, 41, etc.)
     */
    public function up(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            // NULL = no ECF (regular invoice), integer = ECF type code (31, 32, 41, 43, 44, 45, 46, 47)
            $table->integer('ecf_type')->nullable()->after('currency')
                ->comment('NULL = sin facturación electrónica, 31 = B/F Crédito Fiscal, 32 = B/F Consumidor Final, etc.');
            // TipoIngresos for 606 report: 01–06
            $table->string('tipo_ingresos', 2)->nullable()->default('01')->after('ecf_type');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn(['ecf_type', 'tipo_ingresos']);
        });
    }
};
