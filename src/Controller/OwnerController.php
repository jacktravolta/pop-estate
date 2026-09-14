<?php
namespace App\Controller;
use App\Entity\Owner;
use App\Form\OwnerType;
use App\Repository\OwnerRepository;
use App\Validator\RutHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/owners')]
class OwnerController extends AbstractController
{
    #[Route('', name: 'app_owner_index', methods: ['GET'])]
    public function index(Request $request, OwnerRepository $repo): Response
    {
        $q=trim($request->query->get('q','')); $page=max(1,(int)$request->query->get('page',1)); $perPage=10;
        $kpi=$repo->getKpiData();
        $qbCount=$repo->createFilteredQueryBuilder($q?:null); $qbCount->resetDQLPart('orderBy');
        $total=(int)$qbCount->select('COUNT(o.id)')->getQuery()->getSingleScalarResult();
        $totalPages=max(1,(int)ceil($total/$perPage));
        $qb=$repo->createFilteredQueryBuilder($q?:null);
        $owners=$qb->setFirstResult(($page-1)*$perPage)->setMaxResults($perPage)->getQuery()->getResult();
        if($request->isXmlHttpRequest()) return $this->render('owner/_table_rows.html.twig',['owners'=>$owners,'repo'=>$repo]);
        return $this->render('owner/index.html.twig',['owners'=>$owners,'kpi'=>$kpi,'q'=>$q,'page'=>$page,'totalPages'=>$totalPages,'totalResults'=>$total,'repo'=>$repo]);
    }

    #[Route('/new', name: 'app_owner_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, OwnerRepository $repo): Response
    {
        $owner=new Owner(); $form=$this->createForm(OwnerType::class,$owner); $form->handleRequest($request);
        if($form->isSubmitted()){
            if(!$form->isValid()){
                $errors=[]; foreach($form->getErrors(true) as $e) $errors[]=$e->getMessage();
                $this->addFlash('danger','Errores: '.implode(', ',$errors));
            } else {
                $rutCanonica = RutHelper::format($owner->getRut());
                $owner->setRut($rutCanonica);
                $exist=$repo->findByRut($rutCanonica);
                if($exist){
                    $this->addFlash('danger',"RUT {$rutCanonica} ya existe (propietario #{$exist->getId()} - {$exist->getNombre()})");
                } else {
                    $em->persist($owner); $em->flush();
                    $this->addFlash('success','Propietario #'.$owner->getId().' creado');
                    if($request->isXmlHttpRequest()) return new Response('OK',200);
                    return $this->redirectToRoute('app_owner_index');
                }
            }
        }
        return $this->render('owner/new.html.twig',['form'=>$form->createView(),'owner'=>$owner]);
    }

    #[Route('/{id}', name: 'app_owner_show', requirements: ['id'=>'\d+'], methods: ['GET'])]
    public function show(Owner $owner, OwnerRepository $repo): Response
    {
        return $this->render('owner/show.html.twig',['owner'=>$owner,'propCount'=>$repo->countPropertiesByOwner($owner),'hasProperties'=>$repo->hasActiveProperties($owner)]);
    }

    #[Route('/{id}/edit', name: 'app_owner_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Owner $owner, EntityManagerInterface $em, OwnerRepository $repo): Response
    {
        $form=$this->createForm(OwnerType::class,$owner); $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $rutCanonica = RutHelper::format($owner->getRut());
            $owner->setRut($rutCanonica);
            $exist=$repo->findByRut($rutCanonica);
            if($exist && $exist->getId()!==$owner->getId()){
                $this->addFlash('danger',"RUT {$rutCanonica} ya existe en propietario #{$exist->getId()} - {$exist->getNombre()}");
            } else {
                $em->flush(); $this->addFlash('success','Actualizado');
                if($request->isXmlHttpRequest()) return new Response('OK',200);
                return $this->redirectToRoute('app_owner_index');
            }
        }
        return $this->render('owner/edit.html.twig',['owner'=>$owner,'form'=>$form->createView()]);
    }

    #[Route('/{id}/delete', name: 'app_owner_delete', methods: ['POST'])]
    public function delete(Request $request, Owner $owner, EntityManagerInterface $em, OwnerRepository $repo): Response
    {
        if(!$this->isCsrfTokenValid('delete'.$owner->getId(),$request->request->get('_token'))){ $this->addFlash('danger','CSRF inválido'); return $this->redirectToRoute('app_owner_index'); }
        if($repo->hasActiveProperties($owner)){ $count=$repo->countPropertiesByOwner($owner); $this->addFlash('danger',"No se puede eliminar: tiene $count propiedad(es)"); return $this->redirectToRoute('app_owner_index'); }
        $em->remove($owner); $em->flush(); $this->addFlash('success',"Propietario eliminado"); return $this->redirectToRoute('app_owner_index');
    }
}
