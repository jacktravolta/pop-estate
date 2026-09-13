<?php
namespace App\Service\AI;
use Doctrine\DBAL\Connection;

class RagService
{
    private const THRESHOLD = 0.75;
    private const TOP_K = 5;

    public function __construct(
        private Connection $connection,
        private EmbeddingService $embeddingService
    ) {}

    public function retrieve(string $question): array
    {
        if (!$this->embeddingService->isAvailable()) {
            return [];
        }

        $embedding = $this->embeddingService->embed($question);
        if (empty($embedding)) {
            return [];
        }

        $embeddingStr = '[' . implode(',', $embedding) . ']';
        
        $sql = "SELECT id, content, source_type, source_id, metadata,
                1 - (embedding <=> :embedding::vector) AS similarity
                FROM document
                WHERE 1 - (embedding <=> :embedding::vector) > :threshold
                ORDER BY embedding <=> :embedding::vector
                LIMIT :limit";

        $results = $this->connection->fetchAllAssociative($sql, [
            'embedding' => $embeddingStr,
            'threshold' => self::THRESHOLD,
            'limit' => self::TOP_K,
        ]);

        return $results;
    }
}
