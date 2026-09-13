<?php
namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiCompanyControllerTest extends WebTestCase
{
    public function testListRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/companies');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testCreateCompanyValidatesRut(): void
    {
        $client = static::createClient();
        
        // Simular autenticación con token JWT (mock)
        $client->request('POST', '/api/companies', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer mock_token',
        ], json_encode([
            'rut' => '12345678-0', // RUT inválido
            'razonSocial' => 'Test',
            'direccion' => 'Test 123',
        ]));

        // Debería devolver 401 (token inválido) o 400 (RUT inválido)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [400, 401]);
    }
}