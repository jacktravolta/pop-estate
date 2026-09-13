<?php
namespace App\Controller;
use App\Entity\Company;
use App\Form\CompanyType;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/companies')]
class CompanyController extends AbstractController {
    #[Route('', name: 'app_company_index', methods: ['GET'])]
    public function index(Request $request, CompanyRepository $repo, PaginatorInterface $paginator): Response {
        $q = trim($request->query->get('q','')); $qb = $repo->createQueryBuilder('c')->orderBy('c.id','DESC');
        if ($q) { $qb->where('c.razonSocial LIKE :q OR c.rut LIKE :q')->setParameter('q','%'.$q.'%'); }
        $pagination = $paginator->paginate($qb, $request->query->getInt('page',1), 12);
        return $this->render('company/index.html.twig',['pagination'=>$pagination,'q'=>$q]);
    }
    #[Route('/new', name: 'app_company_new', methods: ['GET','POST'])] public function new(Request $request, EntityManagerInterface $em): Response { $c=new Company(); $f=$this->createForm(CompanyType::class,$c); $f->handleRequest($request); if($f->isSubmitted()&&$f->isValid()){$em->persist($c);$em->flush();return $this->redirectToRoute('app_company_index');} return $this->render('company/new.html.twig',['form'=>$f]); }
    #[Route('/{id}', name: 'app_company_show', methods: ['GET'])] public function show(Company $c): Response { return $this->render('company/show.html.twig',['company'=>$c]); }
    #[Route('/{id}/edit', name: 'app_company_edit', methods: ['GET','POST'])] public function edit(Request $request, Company $c, EntityManagerInterface $em): Response { $f=$this->createForm(CompanyType::class,$c); $f->handleRequest($request); if($f->isSubmitted()&&$f->isValid()){$em->flush();return $this->redirectToRoute('app_company_index');} return $this->render('company/edit.html.twig',['form'=>$f,'company'=>$c]); }
    #[Route('/{id}', name: 'app_company_delete', methods: ['POST'])] public function delete(Request $request, Company $c, EntityManagerInterface $em): Response { if($this->isCsrfTokenValid('delete'.$c->getId(),$request->request->get('_token'))){$em->remove($c);$em->flush();} return $this->redirectToRoute('app_company_index'); }
}
