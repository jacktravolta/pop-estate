<?php
namespace App\Controller;
use App\Entity\Property;
use App\Entity\Settlement;
use App\Form\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route("/properties")]
class PropertyController extends AbstractController
{
    #[Route("/", name: "app_property_index", methods: ["GET"])]
    public function index(Request $request, PropertyRepository $repo, EntityManagerInterface $em): Response
    {
        $page = max(1, (int)$request->query->get("page", 1));
        $limit = 50; $offset = ($page-1)*$limit;
        $q = $request->query->get("q");
        $qb = $repo->createQueryBuilder("p")->leftJoin("p.owner","o")->addSelect("o");
        if($q){ $qb->where("LOWER(p.direccion) LIKE :q OR LOWER(p.comuna) LIKE :q OR LOWER(p.rol) LIKE :q OR LOWER(o.nombre) LIKE :q")->setParameter("q","%".strtolower($q)."%"); }
        $total = (clone $qb)->select("COUNT(p.id)")->getQuery()->getSingleScalarResult();
        $properties = $qb->orderBy("p.id","DESC")->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();
        
        $metrics = ["total"=>$total,"ocupadas"=>0,"vacantes"=>0,"ocupacionPct"=>0,"valorPromedio"=>0];
        try{ $metrics["ocupadas"] = $em->getRepository(Settlement::class)->count([]); }catch(\Exception $e){}
        $metrics["vacantes"] = max(0,$total-$metrics["ocupadas"]);
        $metrics["ocupacionPct"] = $total>0 ? round($metrics["ocupadas"]*100/$total) : 0;

        // Ahora traemos COUNT y el ID de la primera liquidación por propiedad
        $rows = $em->createQueryBuilder()
            ->select("IDENTITY(s.property) as pid, COUNT(s.id) as cnt, MIN(s.id) as firstId")
            ->from(Settlement::class,"s")
            ->groupBy("s.property")
            ->getQuery()->getResult();
        $blockedMap=[]; $firstMap=[];
        foreach($rows as $r){ $blockedMap[(int)$r["pid"]] = (int)$r["cnt"]; $firstMap[(int)$r["pid"]] = (int)$r["firstId"]; }

        return $this->render("property/index.html.twig", [
            "properties"=>$properties,
            "metrics"=>$metrics,
            "blockedMap"=>$blockedMap,
            "firstMap"=>$firstMap,
            "blockedIds"=>array_keys($blockedMap),
            "total"=>$total,"page"=>$page,"pages"=>ceil($total/$limit),"q"=>$q
        ]);
    }
    #[Route("/new", name: "app_property_new", methods: ["GET","POST"])]
    public function new(Request $r, EntityManagerInterface $em): Response { $p=new Property(); $f=$this->createForm(PropertyType::class,$p); $f->handleRequest($r); if($f->isSubmitted()&&$f->isValid()){ $em->persist($p); $em->flush(); if($r->isXmlHttpRequest()) return new Response("OK"); return $this->redirectToRoute("app_property_index"); } return $this->render("property/new.html.twig",["property"=>$p,"form"=>$f]); }
    #[Route("/{id}/edit", name: "app_property_edit", methods: ["GET","POST"])]
    public function edit(Request $r, Property $p, EntityManagerInterface $em): Response { $f=$this->createForm(PropertyType::class,$p); $f->handleRequest($r); if($f->isSubmitted()&&$f->isValid()){ $em->flush(); if($r->isXmlHttpRequest()) return new Response("OK"); return $this->redirectToRoute("app_property_index"); } return $this->render("property/edit.html.twig",["property"=>$p,"form"=>$f]); }
    #[Route("/{id}", name: "app_property_delete", methods: ["GET","POST"])]
    public function delete(Request $r, Property $p, EntityManagerInterface $em): Response { if($this->isCsrfTokenValid("delete".$p->getId(),$r->request->get("_token"))){ $c=$em->getRepository(Settlement::class)->count(["property"=>$p]); if($c>0){ $this->addFlash("error","#".$p->getId()." bloqueada: tiene ".$c." liq."); return $this->redirectToRoute("app_property_index"); } $em->remove($p); $em->flush(); $this->addFlash("success","#".$p->getId()." eliminada"); } return $this->redirectToRoute("app_property_index",[],Response::HTTP_SEE_OTHER); }
}
