<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;

class SaasPreDeploymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the in-memory database so settings and default users exist
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test that the SPA welcome page loads correctly.
     */
    public function test_home_page_returns_ok(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('id="app"', false);
    }

    /**
     * Test that public billing/payment and info pages work.
     */
    public function test_public_routes_load_correctly(): void
    {
        // Public invoice search page
        $response = $this->get('/buscar-factura');
        $response->assertStatus(200);

        // API documentation
        $response = $this->get('/api-docs');
        $response->assertStatus(200);

        // Diagnostics page
        $response = $this->get('/diagnostics');
        $response->assertStatus(200);
    }

    /**
     * Test that protected API endpoints return 401 Unauthorized for guests.
     */
    public function test_protected_endpoints_require_authentication(): void
    {
        $headers = ['Accept' => 'application/json'];

        // Invoices endpoint
        $response = $this->getJson('/api/invoices', $headers);
        $response->assertStatus(401);

        // Clients endpoint
        $response = $this->getJson('/api/clients', $headers);
        $response->assertStatus(401);

        // Full Settings endpoint (should not be readable by public)
        $response = $this->getJson('/api/settings', $headers);
        $response->assertStatus(401);
    }

    /**
     * Test the public settings endpoint.
     * It should respond to unauthenticated requests and must not leak secrets.
     */
    public function test_public_settings_endpoint_works_and_is_safe(): void
    {
        $response = $this->getJson('/api/settings/public');
        
        $response->assertStatus(200);

        // Assert public visual keys are present
        $response->assertJsonStructure([
            'company_name',
            'company_logo',
            'login_logo',
            'company_favicon',
            'pdf_primary_color',
            'pdf_accent_color',
            'is_installed',
            'system_version',
            'system_changelog',
        ]);

        // Verify no sensitive keys are leaked
        $data = $response->json();
        
        $sensitiveKeys = [
            'smtp_password',
            'smtp_username',
            'smtp_host',
            'whatsapp_access_token',
            'dgii_certificate_password',
            'db_password',
            'database_password',
            'password',
            'secret',
        ];

        foreach ($sensitiveKeys as $key) {
            $this->assertArrayNotHasKey($key, $data, "Security Leak: Sensitive key '{$key}' was found in the public settings response!");
        }
    }

    /**
     * Test that DGII integration endpoints exist and return the correct XML/JSON formats.
     */
    public function test_dgii_endpoints_exist_and_conform_to_spec(): void
    {
        // 1. Semilla Endpoint (Autenticación)
        $responseSemilla = $this->get('/fe/autenticacion/api/semilla');
        $responseSemilla->assertStatus(200);
        $responseSemilla->assertHeader('Content-Type', 'application/xml');
        $responseSemilla->assertSee('<Semilla', false);
        $responseSemilla->assertSee('<UUID>', false);
        $responseSemilla->assertSee('<Fecha>', false);

        // 2. Validación de Certificado Endpoint (Autenticación)
        $responseValCert = $this->postJson('/fe/autenticacion/api/validacioncertificado');
        $responseValCert->assertStatus(200);
        // Should return JSON by default
        $responseValCert->assertJsonStructure(['token', 'expira', 'expedido']);

        // With XML Accept Header
        $responseValCertXml = $this->post('/fe/autenticacion/api/validacioncertificado', [], [
            'Accept' => 'application/xml'
        ]);
        $responseValCertXml->assertStatus(200);
        $responseValCertXml->assertHeader('Content-Type', 'application/xml');
        $responseValCertXml->assertSee('<RespuestaAutenticacion>', false);
        $responseValCertXml->assertSee('<token>', false);

        // 3. Recepción e-CF Webhook (ARECF Acuse de Recibo)
        $responseRecepcion = $this->post('/fe/recepcion/api/ecf');
        $responseRecepcion->assertStatus(200);
        $responseRecepcion->assertHeader('Content-Type', 'application/xml');
        $responseRecepcion->assertSee('<ARECF', false);
        $responseRecepcion->assertSee('<DetalleAcusedeRecibo>', false);

        // 4. Aprobación Comercial Webhook (ACECF)
        $responseAprobacion = $this->post('/fe/aprobacioncomercial/api/ecf');
        $responseAprobacion->assertStatus(200);
        $responseAprobacion->assertHeader('Content-Type', 'application/xml');
        $responseAprobacion->assertSee('<ACECF', false);
        $responseAprobacion->assertSee('<DetalleAprobacionComercial>', false);
    }
}

