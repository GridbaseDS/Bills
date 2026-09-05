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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'anulation_type')) {
                $table->string('anulation_type', 2)->nullable()->after('status')
                    ->comment('DGII 608 Anulation Code (01-09)');
            }
            if (!Schema::hasColumn('invoices', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('anulation_type');
            }
            if (!Schema::hasColumn('invoices', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['anulation_type', 'cancellation_reason', 'cancelled_at']);
        });
    }
};
