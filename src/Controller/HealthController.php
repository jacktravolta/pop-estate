<?php
namespace App\Controller;

use App\Service\AI\EmbeddingService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health')]
    public function index(Connection $connection, EmbeddingService $embeddingService): JsonResponse
    {
        $status = [
            'app' => 'healthy',
            'database' => 'healthy',
            'pgvector' => 'healthy',
            'ai' => 'healthy',
            'timestamp' => date('c'),
        ];
        $httpCode = Response::HTTP_OK;

        try {
            $connection->executeQuery('SELECT 1');
            $vectorExt = $connection->fetchOne("SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'vector')");
            $status['pgvector'] = $vectorExt ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $status['database'] = 'unhealthy';
            $status['pgvector'] = 'unhealthy';
            $httpCode = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        if (!$embeddingService->isAvailable()) {
            $status['ai'] = 'degraded';
        }

        return new JsonResponse($status, $httpCode);
    }
}