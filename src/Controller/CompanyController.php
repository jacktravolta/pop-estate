<?php
namespace App\Controller;
use App\Entity\Company;
use App\Form\CompanyType;
use App\Repository\CompanyRepository;
use App\Validator\RutHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/companies')]
class CompanyController extends AbstractController
{
    #[Route('', name: 'app_company_index', methods: ['GET'])]
    public function index(Request $request, CompanyRepository $repo): Response
    {
        $q = trim($request->query->get('q','')); $page = max(1,(int)$request->query->get('page',1)); $perPage=10;
        $kpi=$repo->getKpiData();
        $qbCount=$repo->createFilteredQueryBuilder($q?:null); $qbCount->resetDQLPart('orderBy');
        $total=(int)$qbCount->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
        $totalPages=max(1,(int)ceil($total/$perPage));
        $qb=$repo->createFilteredQueryBuilder($q?:null);
        $companies=$qb->setFirstResult(($page-1)*$perPage)->setMaxResults($perPage)->getQuery()->getResult();
        if($request->isXmlHttpRequest()) return $this->render('company/_table_rows.html.twig',['companies'=>$companies,'repo'=>$repo,'q'=>$q]);
        return $this->render('company/index.html.twig',['companies'=>$companies,'kpi'=>$kpi,'q'=>$q,'page'=>$page,'totalPages'=>$totalPages,'totalResults'=>$total,'repo'=>$repo]);
    }

    #[Route('/new', name: 'app_company_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, CompanyRepository $repo): Response
    {
        $company=new Company(); $form=$this->createForm(CompanyType::class,$company); $form->handleRequest($request);
        if($form->isSubmitted()){
            if(!$form->isValid()){
                $errs=[]; foreach($form->getErrors(true) as $e) $errs[]=$e->getMessage();
                $this->addFlash('danger','Errores: '.implode(' | ',$errs));
            } else {
                // Normaliza RUT a formato canónico 14.137.654-3
                $rutCanonica = RutHelper::format($company->getRut());
                $company->setRut($rutCanonica);

                $exist = $repo->findByRut($rutCanonica);
                if($exist){
                    $this->addFlash('danger',"RUT {$rutCanonica} ya existe (empresa #{$exist->getId()} - {$exist->getRazonSocial()})");
                    // No persist, no flush, return form con error
                } else {
                    if($this->getUser()) $company->setCreatedBy($this->getUser());
                    $company->setUpdatedAt(new \DateTimeImmutable());
                    $em->persist($company); $em->flush();
                    $this->addFlash('success','Empresa #'.$company->getId().' creada');
                    if($request->isXmlHttpRequest()) return new Response('OK',200);
                    return $this->redirectToRoute('app_company_index');
                }
            }
        }
        return $this->render('company/new.html.twig',['form'=>$form->createView(),'company'=>$company]);
    }

    #[Route('/{id}', name: 'app_company_show', requirements: ['id'=>'\d+'], methods: ['GET'])]
    public function show(Company $company, CompanyRepository $repo): Response
    {
        return $this->render('company/show.html.twig',['company'=>$company,'propCount'=>$repo->countPropertiesByCompany($company),'hasProps'=>$repo->hasActiveProperties($company)]);
    }

    #[Route('/{id}/edit', name: 'app_company_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Company $company, EntityManagerInterface $em, CompanyRepository $repo): Response
    {
        $form=$this->createForm(CompanyType::class,$company); $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $rutCanonica = RutHelper::format($company->getRut());
            $company->setRut($rutCanonica);
            $exist=$repo->findByRut($rutCanonica);
            if($exist && $exist->getId()!==$company->getId()){
                $this->addFlash('danger',"RUT {$rutCanonica} ya existe en empresa #{$exist->getId()} - {$exist->getRazonSocial()}");
            } else {
                $company->setUpdatedAt(new \DateTimeImmutable()); $em->flush();
                $this->addFlash('success','Empresa actualizada');
                if($request->isXmlHttpRequest()) return new Response('OK',200);
                return $this->redirectToRoute('app_company_index');
            }
        }
        return $this->render('company/edit.html.twig',['company'=>$company,'form'=>$form->createView()]);
    }

    #[Route('/{id}/delete', name: 'app_company_delete', methods: ['POST'])]
    public function delete(Request $request, Company $company, EntityManagerInterface $em, CompanyRepository $repo): Response
    {
        if(!$this->isCsrfTokenValid('delete'.$company->getId(),$request->request->get('_token'))){ $this->addFlash('danger','CSRF inválido'); return $this->redirectToRoute('app_company_index'); }
        if($repo->hasActiveProperties($company)){ $count=$repo->countPropertiesByCompany($company); $this->addFlash('danger',"No se puede eliminar: tiene $count propiedad(es) activa(s)"); return $this->redirectToRoute('app_company_index'); }
        $company->softDelete(); $em->flush(); $this->addFlash('success',"Empresa '{$company->getRazonSocial()}' archivada"); return $this->redirectToRoute('app_company_index');
    }
}
