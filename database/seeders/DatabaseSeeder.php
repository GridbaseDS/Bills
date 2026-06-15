<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Provide default admin
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@gridbase.com.do'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );

        // Provide default support admin
        \App\Models\User::firstOrCreate(
            ['email' => 'soporte@gridbase.com.do'],
            [
                'name' => 'Soporte Gridbase',
                'password' => bcrypt('SamDP_9903'),
                'role' => 'admin',
            ]
        );

        $settings = [
            ['setting_key' => 'company_name', 'setting_value' => 'Gridbase Digital Solutions', 'setting_group' => 'company'],
            ['setting_key' => 'company_email', 'setting_value' => 'bills@gridbase.com.do', 'setting_group' => 'company'],
            ['setting_key' => 'company_phone', 'setting_value' => '', 'setting_group' => 'company'],
            ['setting_key' => 'company_address', 'setting_value' => '', 'setting_group' => 'company'],
            ['setting_key' => 'company_city', 'setting_value' => '', 'setting_group' => 'company'],
            ['setting_key' => 'company_country', 'setting_value' => '', 'setting_group' => 'company'],
            ['setting_key' => 'company_tax_id', 'setting_value' => '', 'setting_group' => 'company'],
            ['setting_key' => 'company_website', 'setting_value' => 'https://gridbase.com.do', 'setting_group' => 'company'],
            ['setting_key' => 'default_currency', 'setting_value' => 'USD', 'setting_group' => 'invoice'],
            ['setting_key' => 'default_tax_rate', 'setting_value' => '0.00', 'setting_group' => 'invoice'],
            ['setting_key' => 'invoice_pdf_template', 'setting_value' => 'normal', 'setting_group' => 'invoice'],
            ['setting_key' => 'tax_label', 'setting_value' => 'Tax', 'setting_group' => 'invoice'],
            ['setting_key' => 'invoice_prefix', 'setting_value' => 'GBS-', 'setting_group' => 'invoice'],
            ['setting_key' => 'invoice_next_number', 'setting_value' => '1001', 'setting_group' => 'invoice'],
            ['setting_key' => 'quote_prefix', 'setting_value' => 'QUO-', 'setting_group' => 'invoice'],
            ['setting_key' => 'quote_next_number', 'setting_value' => '1001', 'setting_group' => 'invoice'],
            ['setting_key' => 'default_due_days', 'setting_value' => '30', 'setting_group' => 'invoice'],
            ['setting_key' => 'default_quote_validity', 'setting_value' => '15', 'setting_group' => 'invoice'],
            ['setting_key' => 'default_notes', 'setting_value' => '', 'setting_group' => 'invoice'],
            ['setting_key' => 'default_terms', 'setting_value' => 'Payment is due within the specified due date.', 'setting_group' => 'invoice'],
            ['setting_key' => 'smtp_host', 'setting_value' => '', 'setting_group' => 'email'],
            ['setting_key' => 'smtp_port', 'setting_value' => '587', 'setting_group' => 'email'],
            ['setting_key' => 'smtp_username', 'setting_value' => '', 'setting_group' => 'email'],
            ['setting_key' => 'smtp_password', 'setting_value' => '', 'setting_group' => 'email'],
            ['setting_key' => 'smtp_encryption', 'setting_value' => 'tls', 'setting_group' => 'email'],
            ['setting_key' => 'smtp_from_name', 'setting_value' => 'Gridbase Digital Solutions', 'setting_group' => 'email'],
            ['setting_key' => 'smtp_from_email', 'setting_value' => '', 'setting_group' => 'email'],
            ['setting_key' => 'whatsapp_access_token', 'setting_value' => '', 'setting_group' => 'whatsapp'],
            ['setting_key' => 'whatsapp_phone_id', 'setting_value' => '', 'setting_group' => 'whatsapp'],
            ['setting_key' => 'whatsapp_business_id', 'setting_value' => '', 'setting_group' => 'whatsapp'],
            ['setting_key' => 'whatsapp_enabled', 'setting_value' => '0', 'setting_group' => 'whatsapp'],
            ['setting_key' => 'reminders_enabled', 'setting_value' => '1', 'setting_group' => 'automation'],
            ['setting_key' => 'reminders_days_before', 'setting_value' => '3', 'setting_group' => 'automation'],
            ['setting_key' => 'reminders_overdue_interval', 'setting_value' => '7', 'setting_group' => 'automation'],
            ['setting_key' => 'payment_link_general', 'setting_value' => '', 'setting_group' => 'integrations'],
            ['setting_key' => 'bank_instructions', 'setting_value' => '', 'setting_group' => 'integrations'],
            // DGII / e-CF settings
            ['setting_key' => 'dgii_env', 'setting_value' => 'testing', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_ncf_expiry_date', 'setting_value' => '2028-12-31', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_certificate_path', 'setting_value' => '', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_certificate_password', 'setting_value' => '', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_razon_social', 'setting_value' => '', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_nombre_comercial', 'setting_value' => '', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_municipio', 'setting_value' => '', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_provincia', 'setting_value' => '', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_31', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_32', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_33', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_34', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_41', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_43', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_44', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_45', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_46', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'dgii_next_e_ncf_47', 'setting_value' => '1', 'setting_group' => 'dgii'],
            ['setting_key' => 'logo_capsule_theme', 'setting_value' => 'dark', 'setting_group' => 'company'],
            ['setting_key' => 'is_installed', 'setting_value' => '0', 'setting_group' => 'system'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::firstOrCreate(
                ['setting_key' => $setting['setting_key']],
                [
                    'setting_value' => $setting['setting_value'],
                    'setting_group' => $setting['setting_group']
                ]
            );
        }
    }
}
