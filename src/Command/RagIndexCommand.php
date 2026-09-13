<?php
namespace App\Command;

use App\Service\AI\EmbeddingService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:rag:index', description: 'Indexa todos los datos en pgvector para RAG')]
class RagIndexCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private EmbeddingService $embeddingService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Indexando datos para RAG');

        if (!$this->embeddingService->isAvailable()) {
            $io->error('Ollama no está disponible. Verifica que el contenedor esté corriendo.');
            return Command::FAILURE;
        }

        // Asegurar que la tabla document tenga la columna embedding
        $this->ensureEmbeddingColumn();

        // Limpiar documentos anteriores
        $this->connection->executeStatement('DELETE FROM document');
        $io->info('Documentos anteriores eliminados');

        $count = 0;

        // Indexar empresas
        $companies = $this->connection->fetchAllAssociative(
            'SELECT id, rut, razon_social, giro, comuna, ciudad FROM company WHERE deleted_at IS NULL'
        );
        foreach ($companies as $c) {
            $content = sprintf('Empresa: %s, RUT: %s, Giro: %s, Comuna: %s, Ciudad: %s',
                $c['razon_social'], $c['rut'], $c['giro'] ?? 'N/A', $c['comuna'] ?? 'N/A', $c['ciudad'] ?? 'N/A');
            $this->indexDocument($content, 'company', (int) $c['id']);
            $count++;
        }
        $io->info(sprintf('✓ %d empresas indexadas', count($companies)));

        // Indexar propietarios
        $owners = $this->connection->fetchAllAssociative(
            'SELECT id, rut, nombre, comuna, ciudad FROM owner'
        );
        foreach ($owners as $o) {
            $content = sprintf('Propietario: %s, RUT: %s, Comuna: %s, Ciudad: %s',
                $o['nombre'], $o['rut'], $o['comuna'] ?? 'N/A', $o['ciudad'] ?? 'N/A');
            $this->indexDocument($content, 'owner', (int) $o['id']);
            $count++;
        }
        $io->info(sprintf('✓ %d propietarios indexados', count($owners)));

        // Indexar propiedades
        $properties = $this->connection->fetchAllAssociative(
            'SELECT p.id, p.direccion, p.rol, p.comuna, p.ciudad, c.razon_social as empresa, o.nombre as propietario
             FROM property p
             JOIN company c ON p.company_id = c.id
             JOIN owner o ON p.owner_id = o.id
             WHERE p.deleted_at IS NULL'
        );
        foreach ($properties as $p) {
            $content = sprintf('Propiedad: %s, Rol: %s, Comuna: %s, Empresa: %s, Propietario: %s',
                $p['direccion'], $p['rol'] ?? 'N/A', $p['comuna'] ?? 'N/A', $p['empresa'], $p['propietario']);
            $this->indexDocument($content, 'property', (int) $p['id']);
            $count++;
        }
        $io->info(sprintf('✓ %d propiedades indexadas', count($properties)));

        // Indexar liquidaciones
        $settlements = $this->connection->fetchAllAssociative(
            'SELECT s.id, s.estado, s.total_neto, s.iva, s.total, s.fecha_inicio, s.fecha_termino,
                    p.direccion as propiedad
             FROM settlement s
             JOIN property p ON s.property_id = p.id'
        );
        foreach ($settlements as $s) {
            $content = sprintf('Liquidación #%d: Propiedad %s, Estado: %s, Periodo: %s a %s, Neto: $%s, IVA: $%s, Total: $%s',
                $s['id'], $s['propiedad'], $s['estado'], $s['fecha_inicio'], $s['fecha_termino'],
                $s['total_neto'], $s['iva'], $s['total']);
            $this->indexDocument($content, 'settlement', (int) $s['id']);
            $count++;
        }
        $io->info(sprintf('✓ %d liquidaciones indexadas', count($settlements)));

        // Indexar facturas
        $invoices = $this->connection->fetchAllAssociative(
            'SELECT id, folio, emisor, rut_emisor, periodo, neto, iva, total, estado FROM invoice'
        );
        foreach ($invoices as $i) {
            $content = sprintf('Factura Folio #%d: Emisor %s (%s), Periodo: %s, Neto: $%s, IVA: $%s, Total: $%s, Estado: %s',
                $i['folio'], $i['emisor'], $i['rut_emisor'], $i['periodo'],
                $i['neto'], $i['iva'], $i['total'], $i['estado']);
            $this->indexDocument($content, 'invoice', (int) $i['id']);
            $count++;
        }
        $io->info(sprintf('✓ %d facturas indexadas', count($invoices)));

        $io->success(sprintf('Indexación completada: %d documentos en total', $count));
        return Command::SUCCESS;
    }

    private function ensureEmbeddingColumn(): void
    {
        $exists = $this->connection->fetchOne(
            "SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_name='document' AND column_name='embedding')"
        );

        if (!$exists) {
            $this->connection->executeStatement('ALTER TABLE document ADD COLUMN embedding vector(768)');
            $this->connection->executeStatement('CREATE INDEX idx_document_embedding ON document USING ivfflat (embedding vector_cosine_ops)');
        }
    }

    private function indexDocument(string $content, string $sourceType, int $sourceId): void
    {
        try {
            $embedding = $this->embeddingService->embed($content);
            $embeddingStr = '[' . implode(',', $embedding) . ']';

            $this->connection->executeStatement(
                'INSERT INTO document (content, source_type, source_id, embedding, created_at) VALUES (:content, :sourceType, :sourceId, :embedding::vector, NOW())',
                [
                    'content' => $content,
                    'sourceType' => $sourceType,
                    'sourceId' => $sourceId,
                    'embedding' => $embeddingStr,
                ]
            );
        } catch (\Exception $e) {
            // Silenciar errores individuales para continuar con el resto
        }
    }
}
