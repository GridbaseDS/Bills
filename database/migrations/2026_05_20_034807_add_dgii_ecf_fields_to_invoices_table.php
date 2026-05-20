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
            $table->boolean('is_ecf')->default(false)->after('client_id');
            $table->integer('ecf_type')->nullable()->after('is_ecf');
            $table->string('encf', 13)->nullable()->unique()->after('ecf_type');
            $table->string('dgii_status', 20)->default('draft')->after('encf');
            $table->string('dgii_track_id', 50)->nullable()->after('dgii_status');
            $table->string('security_code', 6)->nullable()->after('dgii_track_id');
            $table->string('signed_xml_path', 255)->nullable()->after('security_code');
            $table->text('dgii_error_messages')->nullable()->after('signed_xml_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'is_ecf',
                'ecf_type',
                'encf',
                'dgii_status',
                'dgii_track_id',
                'security_code',
                'signed_xml_path',
                'dgii_error_messages',
            ]);
        });
    }
};
