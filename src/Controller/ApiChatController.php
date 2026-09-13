<?php
namespace App\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
class ApiChatController extends AbstractController{
  #[Route('/chatbot/api', name:'api_chat', methods:['POST','GET'])]
  public function chat(Request $request, EntityManagerInterface $em): JsonResponse{
    try{
      $body=json_decode($request->getContent(),true);
      $q=strtolower(trim($body['message'] ?? $request->query->get('q') ?? 'hola'));
      $conn=$em->getConnection();
      $totalProps=(int)$conn->fetchOne('SELECT COUNT(*) FROM property');
      $totalComp=(int)$conn->fetchOne('SELECT COUNT(*) FROM company');
      $totalOwn=(int)$conn->fetchOne('SELECT COUNT(*) FROM owner');
      $totalSett=(int)$conn->fetchOne('SELECT COUNT(*) FROM settlement');
      $totalInv=(int)$conn->fetchOne('SELECT COUNT(*) FROM invoice');
      $sumSett=$conn->fetchOne('SELECT COALESCE(SUM(total),0) FROM settlement');
      $props=[];
      if(strlen($q)>2){ try{ $props=$conn->fetchAllAssociative('SELECT id,direccion,comuna FROM property WHERE direccion ILIKE :q OR comuna ILIKE :q LIMIT 5',['q'=>"%$q%"]);}catch(\Throwable $e){} }
      $ans="RAG REAL - Postgres\nProps:$totalProps Comps:$totalComp Own:$totalOwn Sett:$totalSett Inv:$totalInv Total:$" . number_format((float)$sumSett,0,',','.') . "\nQ:'$q' Matches:".count($props);
      foreach($props as $p) $ans.="\n- {$p['direccion']} {$p['comuna']}";
      return new JsonResponse(['answer'=>$ans]);
    }catch(\Throwable $e){ return new JsonResponse(['answer'=>'Error: '.$e->getMessage()],200); }
  }
}
