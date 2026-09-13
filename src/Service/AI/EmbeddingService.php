<?php
namespace App\Service\AI;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class EmbeddingService
{
    private string $ollamaUrl;
    private string $model;

    public function __construct(private HttpClientInterface $httpClient)
    {
        $this->ollamaUrl = $_ENV['OLLAMA_URL'] ?? 'http://ollama:11434';
        $this->model = $_ENV['OLLAMA_EMBEDDING_MODEL'] ?? 'nomic-embed-text';
    }

    public function embed(string $text): array
    {
        $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/embeddings', [
            'json' => ['model' => $this->model, 'prompt' => $text],
            'timeout' => 30,
        ]);

        $data = $response->toArray();
        return $data['embedding'] ?? [];
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', ['timeout' => 3]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}