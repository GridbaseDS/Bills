<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // All settings are stored as key-value rows in the settings table.
        // This migration seeds the default values for Evolution API settings.
        // The 'whatsapp_driver' key controls which driver is active.

        $defaults = [
            // Driver selector: 'meta' (default, backward-compatible) or 'evolution'
            ['setting_key' => 'whatsapp_driver',       'setting_value' => 'meta',             'setting_group' => 'whatsapp'],

            // Evolution API connection settings
            ['setting_key' => 'evolution_api_url',     'setting_value' => '',                 'setting_group' => 'whatsapp'],
            ['setting_key' => 'evolution_api_key',     'setting_value' => '',                 'setting_group' => 'whatsapp'],
            ['setting_key' => 'evolution_instance',    'setting_value' => 'gridbase-bills',   'setting_group' => 'whatsapp'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->updateOrInsert(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        $keys = ['whatsapp_driver', 'evolution_api_url', 'evolution_api_key', 'evolution_instance'];
        DB::table('settings')->whereIn('setting_key', $keys)->delete();
    }
};
