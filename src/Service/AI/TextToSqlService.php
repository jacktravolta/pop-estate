<?php
namespace App\Service\AI;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TextToSqlService
{
    private const SCHEMA = "
Tablas disponibles (usa EXACTAMENTE estos nombres de columnas en snake_case):
- company: id, rut, razon_social, giro, direccion, comuna, ciudad, email, deleted_at
- owner: id, rut, nombre, giro, direccion, comuna, ciudad, email
- property: id, company_id, owner_id, direccion, rol, comuna, ciudad, deleted_at
- settlement: id, property_id, fecha_inicio, fecha_termino, estado ('PENDIENTE', 'PAGADA', 'ANULADA'), total_cargo, total_descuento, total_neto, iva, total
- settlement_item: id, settlement_id, tipo ('CARGO', 'DESCUENTO'), descripcion, monto
- invoice: id, folio, emisor, rut_emisor, fecha, periodo, neto, iva, total, estado ('EMITIDA', 'ANULADA')

Ejemplos de queries válidas:
- '¿Cuántas propiedades tenemos?' => SELECT COUNT(*) FROM property WHERE deleted_at IS NULL
- '¿Cuánto está pendiente de cobro?' => SELECT SUM(total) FROM settlement WHERE estado = 'PENDIENTE'
- '¿Cuántas liquidaciones pagadas hay?' => SELECT COUNT(*) FROM settlement WHERE estado = 'PAGADA'
";

    public function __construct(
        private HttpClientInterface $httpClient,
        private AIConfigService $config
    ) {}

    public function generateSql(string $question): ?string
    {
        if (!$this->config->isEnabled()) return null;

        $prompt = sprintf(
            "Eres un asistente que genera queries SQL PostgreSQL. Genera SOLO la query SQL, sin explicaciones, sin markdown, sin comillas. Usa los nombres de columnas EXACTOS en snake_case.\nEsquema:\n%s\nPregunta: %s\nSQL:",
            self::SCHEMA, $question
        );

        try {
            if ($this->config->isOpenAI()) {
                $response = $this->httpClient->request('POST', $this->config->getChatEndpoint(), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->getOpenAIKey(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->config->getModel(),
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                        'temperature' => 0.1,
                        'max_tokens' => 200,
                    ],
                    'timeout' => 30,
                ]);
                $data = $response->toArray();
                $sql = trim($data['choices'][0]['message']['content'] ?? '');
            } else {
                $response = $this->httpClient->request('POST', $this->config->getChatEndpoint(), [
                    'json' => [
                        'model' => $this->config->getModel(),
                        'prompt' => $prompt,
                        'stream' => false,
                        'options' => ['temperature' => 0.1],
                    ],
                    'timeout' => 30,
                ]);
                $data = $response->toArray();
                $sql = trim($data['response'] ?? '');
            }

            // Limpiar cualquier formato markdown que la IA pueda agregar
            $sql = preg_replace('/^```sql\s*/i', '', $sql);
            $sql = preg_replace('/^```\s*/', '', $sql);
            $sql = preg_replace('/```\s*$/', '', $sql);
            return trim($sql) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
