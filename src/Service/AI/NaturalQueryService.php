<?php
namespace App\Service\AI;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
class NaturalQueryService
{
    private string $ollamaUrl; private string $model;
    public function __construct(private HttpClientInterface $httpClient, private RagService $ragService, private TextToSqlService $textToSqlService, private SqlGuard $sqlGuard, private Connection $readonlyConnection, private EmbeddingService $embeddingService, private ?LoggerInterface $logger = null)
    {
        $this->ollamaUrl = $_ENV['OLLAMA_URL'] ?? 'http://ollama:11434';
        $this->model = $_ENV['OLLAMA_MODEL'] ?? 'qwen2.5:1.5b';
    }
    public function ask(string $question): array
    {
        if (!$this->embeddingService->isAvailable()) return ['answer'=>'IA no disponible','sources'=>[],'status'=>'unavailable'];
        $context=[]; $sqlUsed=null; $sqlResult=null;
        $needsSql = preg_match('/cuánt|cuanto|total|suma|factur|pendiente|pagad/', strtolower($question));
        if ($needsSql) {
            $sql = $this->textToSqlService->generateSql($question);
            if ($sql && $this->sqlGuard->validate($sql)) {
                $sql = $this->sqlGuard->enforceLimit($sql);
                $sqlUsed=$sql;
                try {
                    $this->readonlyConnection->executeStatement("SET LOCAL statement_timeout = '5s'");
                    $sqlResult = $this->readonlyConnection->fetchAllAssociative($sql);
                    $context[]="Resultado SQL: ".json_encode($sqlResult, JSON_UNESCAPED_UNICODE);
                } catch (\Exception $e) { $context[]="No se pudo ejecutar consulta."; }
            }
        }
        $ragDocs = $this->ragService->retrieve($question);
        foreach ($ragDocs as $doc) $context[]=sprintf("[%s #%d] %s",$doc['source_type'],$doc['source_id'],mb_substr($doc['content'],0,800));
        if (empty($context)) return ['answer'=>'No tengo información suficiente','sources'=>[],'status'=>'no_context'];
        $prompt=sprintf("Eres asistente Pop Estate. Solo usa contexto.\nContexto:\n%s\n\nPregunta: %s\nRespuesta breve:",implode("\n\n",$context),$question);
        try {
            $r=$this->httpClient->request('POST',$this->ollamaUrl.'/api/generate',['json'=>['model'=>$this->model,'prompt'=>$prompt,'stream'=>false,'options'=>['temperature'=>0.2,'num_predict'=>300]],'timeout'=>45]);
            $d=$r->toArray(); return ['answer'=>trim($d['response']??''),'sources'=>$ragDocs,'sql_used'=>$sqlUsed,'sql_result'=>$sqlResult,'status'=>'ok'];
        } catch (\Exception $e) { return ['answer'=>'Error generando respuesta','sources'=>$ragDocs,'status'=>'error']; }
    }
}
