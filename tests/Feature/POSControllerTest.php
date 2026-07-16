<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_pos_charge_requires_authentication(): void
    {
        $response = $this->postJson('/api/pos/charge', [
            'amount' => 100.00,
            'invoice_id' => 1
        ]);

        $response->assertStatus(401);
    }

    public function test_pos_charge_fails_if_disabled(): void
    {
        $user = User::first();
        
        $client = Client::create([
            'company_name' => 'Test Client',
            'contact_name' => 'John Doe',
            'email' => 'client@test.com',
            'tax_id' => '101010101'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-001',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'DOP',
            'subtotal' => 100.00,
            'tax_amount' => 18.00,
            'total' => 118.00,
            'amount_paid' => 0.00
        ]);

        Setting::updateOrCreate(['setting_key' => 'pos_enabled'], ['setting_value' => '0']);

        $response = $this->actingAs($user)->postJson('/api/pos/charge', [
            'amount' => 100.00,
            'invoice_id' => $invoice->id
        ]);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('La integración de POS / Verifone está desactivada.', $response->json('message'));
    }

    public function test_pos_charge_success_with_mock_driver(): void
    {
        $user = User::first();

        $client = Client::create([
            'company_name' => 'Test Client',
            'contact_name' => 'John Doe',
            'email' => 'client@test.com',
            'tax_id' => '101010101'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-001',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'DOP',
            'subtotal' => 100.00,
            'tax_amount' => 18.00,
            'total' => 118.00,
            'amount_paid' => 0.00
        ]);

        Setting::updateOrCreate(['setting_key' => 'pos_enabled'], ['setting_value' => '1']);
        Setting::updateOrCreate(['setting_key' => 'pos_driver'], ['setting_value' => 'mock']);

        $response = $this->actingAs($user)->postJson('/api/pos/charge', [
            'amount' => 100.00,
            'invoice_id' => $invoice->id
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('approved', $response->json('status'));
        $this->assertNotEmpty($response->json('auth_code'));
    }
}
