<?php
namespace App\Service\BI;

use App\Service\AI\EmbeddingService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InsightsService
{
    private string $ollamaUrl;
    private string $model;

    public function __construct(
        private HttpClientInterface $httpClient,
        private BiService $biService,
        private EmbeddingService $embeddingService
    ) {
        $this->ollamaUrl = $_ENV['OLLAMA_URL'] ?? 'http://ollama:11434';
        $this->model = $_ENV['OLLAMA_MODEL'] ?? 'qwen2.5:1.5b';
    }

    public function generateInsights(): array
    {
        if (!$this->embeddingService->isAvailable()) {
            return $this->generateRuleBasedInsights();
        }

        try {
            $kpis = $this->biService->getKpis();
            $evolucion = $this->biService->getFacturacionMensual(6);
            $comunas = $this->biService->getDistribucionPorComuna();

            $dataResumen = sprintf(
                "KPIs actuales: %d empresas, %d propiedades, %d propietarios. Liquidaciones: %d pendientes, %d pagadas, %d anuladas. Facturación total pagada: $%s. Ticket promedio: $%s. Tendencia vs mes anterior: %s%%. Evolución últimos 6 meses: %s. Top comunas: %s.",
                $kpis['empresas_activas'],
                $kpis['propiedades_activas'],
                $kpis['propietarios'],
                $kpis['liquidaciones_pendientes'],
                $kpis['liquidaciones_pagadas'],
                $kpis['liquidaciones_anuladas'],
                number_format($kpis['total_facturado'], 0, ',', '.'),
                number_format($kpis['ticket_promedio'], 0, ',', '.'),
                $kpis['tendencia_facturacion'],
                json_encode(array_slice($evolucion, -3)),
                json_encode(array_slice($comunas, 0, 3))
            );

            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $this->model,
                    'prompt' => "Eres un analista BI experto en inmobiliaria chilena. Genera EXACTAMENTE 3 insights accionables en formato JSON array. Cada insight debe tener: 'titulo' (máx 6 palabras), 'descripcion' (1-2 oraciones), 'tipo' (oportunidad|riesgo|tendencia), 'prioridad' (alta|media|baja).\n\nDatos: $dataResumen\n\nResponde SOLO con el array JSON, sin markdown ni explicaciones adicionales.",
                    'stream' => false,
                    'options' => ['temperature' => 0.4],
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray();
            $text = trim($data['response'] ?? '');
            
            // Extraer JSON del texto
            if (preg_match('/\[[\s\S]*\]/', $text, $matches)) {
                $insights = json_decode($matches[0], true);
                if (is_array($insights) && count($insights) > 0) {
                    return $insights;
                }
            }

            return $this->generateRuleBasedInsights();
        } catch (\Exception $e) {
            return $this->generateRuleBasedInsights();
        }
    }

    private function generateRuleBasedInsights(): array
    {
        $kpis = $this->biService->getKpis();
        $insights = [];

        if ($kpis['liquidaciones_pendientes'] > 0) {
            $insights[] = [
                'titulo' => 'Cobranza pendiente',
                'descripcion' => sprintf('Hay $%s pendiente de cobro en %d liquidaciones. Prioriza las vencidas.', 
                    number_format($kpis['total_pendiente'], 0, ',', '.'),
                    $kpis['liquidaciones_pendientes']),
                'tipo' => 'riesgo',
                'prioridad' => 'alta',
            ];
        }

        if ($kpis['tendencia_facturacion'] > 10) {
            $insights[] = [
                'titulo' => 'Crecimiento positivo',
                'descripcion' => sprintf('La facturación creció %s%% vs mes anterior. Mantén el impulso comercial.', $kpis['tendencia_facturacion']),
                'tipo' => 'tendencia',
                'prioridad' => 'media',
            ];
        } elseif ($kpis['tendencia_facturacion'] < -10) {
            $insights[] = [
                'titulo' => 'Caída en facturación',
                'descripcion' => sprintf('La facturación cayó %s%% vs mes anterior. Revisa cartera de clientes.', abs($kpis['tendencia_facturacion'])),
                'tipo' => 'riesgo',
                'prioridad' => 'alta',
            ];
        }

        if ($kpis['ticket_promedio'] > 0) {
            $insights[] = [
                'titulo' => 'Ticket promedio',
                'descripcion' => sprintf('El ticket promedio es $%s. Analiza si puedes aumentar el valor por propiedad.', 
                    number_format($kpis['ticket_promedio'], 0, ',', '.')),
                'tipo' => 'oportunidad',
                'prioridad' => 'media',
            ];
        }

        return array_slice($insights, 0, 3);
    }
}