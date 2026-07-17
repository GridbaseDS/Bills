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

    public function test_pos_charge_success_with_virtual_pos_driver(): void
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
            'invoice_number' => 'INV-002',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'DOP',
            'subtotal' => 200.00,
            'tax_amount' => 36.00,
            'total' => 236.00,
            'amount_paid' => 0.00
        ]);

        Setting::updateOrCreate(['setting_key' => 'pos_enabled'], ['setting_value' => '1']);
        Setting::updateOrCreate(['setting_key' => 'pos_driver'], ['setting_value' => 'virtual_pos']);

        $response = $this->actingAs($user)->postJson('/api/pos/charge', [
            'amount' => 200.00,
            'invoice_id' => $invoice->id
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('pending', $response->json('status'));
        $this->assertNotEmpty($response->json('virtual_url'));
        $this->assertStringContainsString('/pos-simulator/' . $invoice->id, $response->json('virtual_url'));
    }

    public function test_pos_status_and_update_status(): void
    {
        $client = Client::create([
            'company_name' => 'Test Client',
            'contact_name' => 'John Doe',
            'email' => 'client@test.com',
            'tax_id' => '101010101'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-003',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'DOP',
            'subtotal' => 200.00,
            'tax_amount' => 36.00,
            'total' => 236.00,
            'amount_paid' => 0.00
        ]);

        // 1. Initialize cached transaction
        \Illuminate\Support\Facades\Cache::put('pos_tx_' . $invoice->id, [
            'status' => 'pending',
            'amount' => 200.00,
            'invoice_id' => $invoice->id
        ], 600);

        // 2. Check public status route (unauthenticated)
        $response = $this->getJson('/api/pos/status/' . $invoice->id);
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('pending', $response->json('data.status'));

        // 3. Update status to approved (unauthenticated simulator action)
        $updateRes = $this->postJson('/api/pos/update-status', [
            'invoice_id' => $invoice->id,
            'status' => 'approved',
            'auth_code' => '777888',
            'card_number' => '489952******1040',
            'card_type' => 'VISA',
            'message' => 'APROBADA'
        ]);
        $updateRes->assertStatus(200);
        $this->assertTrue($updateRes->json('success'));

        // 4. Verify updated status
        $response = $this->getJson('/api/pos/status/' . $invoice->id);
        $response->assertStatus(200);
        $this->assertEquals('approved', $response->json('data.status'));
        $this->assertEquals('777888', $response->json('data.auth_code'));
    }

    public function test_pos_simulator_view_renders_successfully(): void
    {
        $client = Client::create([
            'company_name' => 'Test Client',
            'contact_name' => 'John Doe',
            'email' => 'client@test.com',
            'tax_id' => '101010101'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-004',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'DOP',
            'subtotal' => 200.00,
            'tax_amount' => 36.00,
            'total' => 236.00,
            'amount_paid' => 0.00
        ]);

        $response = $this->get('/pos-simulator/' . $invoice->id);
        $response->assertStatus(200);
        $response->assertSee('GRIDBASE TPV');
        $response->assertSee('Aprobar Transacción');
    }

    public function test_pos_charge_success_with_cardnet_android_driver(): void
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
            'invoice_number' => 'INV-005',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'DOP',
            'subtotal' => 1500.00,
            'tax_amount' => 270.00,
            'total' => 1770.00,
            'amount_paid' => 0.00
        ]);

        Setting::updateOrCreate(['setting_key' => 'pos_enabled'], ['setting_value' => '1']);
        Setting::updateOrCreate(['setting_key' => 'pos_driver'], ['setting_value' => 'cardnet_android']);
        Setting::updateOrCreate(['setting_key' => 'pos_terminal_ip'], ['setting_value' => '192.168.1.70']);
        Setting::updateOrCreate(['setting_key' => 'pos_terminal_port'], ['setting_value' => '2001']);

        // Mock Cardnet API Response
        \Illuminate\Support\Facades\Http::fake([
            '192.168.1.70:2001/*' => \Illuminate\Support\Facades\Http::response([
                'amount' => '177000 DOP',
                'amountOriginalFormat' => '177000',
                'approbationNumber' => '419886',
                'bankName' => 'Popular',
                'cardInformation' => [
                    'maskedPAN' => '***********9547',
                    'cardSubType' => 'MASTERCARD'
                ],
                'referenceNumber' => '0000118',
                'txnMessage' => 'OPERACION ACEPTADA',
                'txnResult' => 4
            ], 200)
        ]);

        $response = $this->actingAs($user)->postJson('/api/pos/charge', [
            'amount' => 1770.00,
            'invoice_id' => $invoice->id
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('approved', $response->json('status'));
        $this->assertEquals('419886', $response->json('auth_code'));
        $this->assertEquals('***********9547', $response->json('card_number'));
        $this->assertEquals('MASTERCARD', $response->json('card_type'));
    }
}

