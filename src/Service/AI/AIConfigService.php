<?php
namespace App\Service\AI;

class AIConfigService
{
    private string $mode;
    private string $model;
    private string $embeddingModel;
    private string $openaiKey;
    private string $ollamaUrl;

    public function __construct()
    {
        $this->mode = $_ENV['AI_MODE'] ?? 'local';
        $this->model = $_ENV['AI_MODEL'] ?? 'qwen2.5:3b';
        $this->embeddingModel = $_ENV['AI_EMBEDDING_MODEL'] ?? 'nomic-embed-text';
        $this->openaiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->ollamaUrl = $_ENV['OLLAMA_URL'] ?? 'http://ollama:11434';
    }

    public function isEnabled(): bool { return $this->mode !== 'none'; }
    public function isLocal(): bool { return $this->mode === 'local'; }
    public function isOpenAI(): bool { return $this->mode === 'openai'; }
    public function getMode(): string { return $this->mode; }
    public function getModel(): string { return $this->model; }
    public function getEmbeddingModel(): string { return $this->embeddingModel; }
    public function getOpenAIKey(): string { return $this->openaiKey; }
    public function getOllamaUrl(): string { return $this->ollamaUrl; }
    
    public function getChatEndpoint(): string {
        return $this->isOpenAI() ? 'https://api.openai.com/v1/chat/completions' : $this->ollamaUrl . '/api/generate';
    }
    
    public function getEmbeddingEndpoint(): string {
        return $this->isOpenAI() ? 'https://api.openai.com/v1/embeddings' : $this->ollamaUrl . '/api/embeddings';
    }
    
    public function getStatus(): string {
        if (!$this->isEnabled()) return 'disabled';
        if ($this->isLocal()) return 'local';
        if ($this->isOpenAI()) return 'openai';
        return 'unknown';
    }
}
