<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns needed for all e-CF types (33, 34, 41-47) to invoices table.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // TipoIngresos: 01-06 (default 01 = Ingresos por operaciones)
            $table->string('tipo_ingresos', 2)->nullable()->after('ecf_type');

            // For Notas de Credito/Debito (types 33, 34): reference to modified NCF
            $table->string('modified_ncf', 13)->nullable()->after('tipo_ingresos');
            $table->integer('modification_code')->nullable()->after('modified_ncf');
            $table->string('modification_reason', 100)->nullable()->after('modification_code');

            // For Nota de Credito (type 34): indicator
            $table->integer('nota_credito_indicator')->nullable()->after('modification_reason');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_ingresos',
                'modified_ncf',
                'modification_code',
                'modification_reason',
                'nota_credito_indicator',
            ]);
        });
    }
};
