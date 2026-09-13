<?php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiDocController extends AbstractController
{
    #[Route('/api/docs', name: 'api_doc', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('api/docs.html.twig');
    }

    #[Route('/api/docs.json', name: 'api_doc_json', methods: ['GET'])]
    public function apiSpec(): \Symfony\Component\HttpFoundation\JsonResponse
    {
        // OpenAPI spec simplificada
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Pop Estate API',
                'version' => '1.0.0',
                'description' => 'API REST para gestión inmobiliaria chilena',
            ],
            'servers' => [['url' => '/api']],
            'components' => [
                'securitySchemes' => [
                    'Bearer' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
            'security' => [['Bearer' => []]],
            'paths' => [
                '/login' => [
                    'post' => [
                        'summary' => 'Obtener token JWT',
                        'tags' => ['Auth'],
                        'security' => [],
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'username' => ['type' => 'string'],
                                            'password' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Token JWT'],
                            '401' => ['description' => 'Credenciales inválidas'],
                        ],
                    ],
                ],
                '/me' => ['get' => ['summary' => 'Usuario actual', 'tags' => ['Auth']]],
                '/companies' => [
                    'get' => ['summary' => 'Listar empresas', 'tags' => ['Companies']],
                    'post' => ['summary' => 'Crear empresa', 'tags' => ['Companies']],
                ],
                '/companies/{id}' => [
                    'get' => ['summary' => 'Ver empresa', 'tags' => ['Companies']],
                    'put' => ['summary' => 'Actualizar empresa', 'tags' => ['Companies']],
                    'delete' => ['summary' => 'Eliminar empresa (soft)', 'tags' => ['Companies']],
                ],
                '/owners' => [
                    'get' => ['summary' => 'Listar propietarios', 'tags' => ['Owners']],
                    'post' => ['summary' => 'Crear propietario', 'tags' => ['Owners']],
                ],
                '/owners/{id}' => [
                    'get' => ['summary' => 'Ver propietario', 'tags' => ['Owners']],
                    'put' => ['summary' => 'Actualizar propietario', 'tags' => ['Owners']],
                ],
                '/properties' => [
                    'get' => ['summary' => 'Listar propiedades', 'tags' => ['Properties']],
                    'post' => ['summary' => 'Crear propiedad', 'tags' => ['Properties']],
                ],
                '/properties/{id}' => [
                    'get' => ['summary' => 'Ver propiedad', 'tags' => ['Properties']],
                    'delete' => ['summary' => 'Eliminar propiedad (soft)', 'tags' => ['Properties']],
                ],
                '/settlements' => [
                    'get' => ['summary' => 'Listar liquidaciones', 'tags' => ['Settlements']],
                    'post' => ['summary' => 'Crear liquidación', 'tags' => ['Settlements']],
                ],
                '/settlements/{id}' => ['get' => ['summary' => 'Ver liquidación', 'tags' => ['Settlements']]],
                '/settlements/{id}/pay' => ['post' => ['summary' => 'Marcar como pagada', 'tags' => ['Settlements']]],
                '/invoices' => [
                    'get' => ['summary' => 'Listar facturas', 'tags' => ['Invoices']],
                    'post' => ['summary' => 'Crear factura', 'tags' => ['Invoices']],
                ],
                '/invoices/{id}' => ['get' => ['summary' => 'Ver factura', 'tags' => ['Invoices']]],
            ],
        ];

        return new Response(
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}