<?php
namespace App\Controller;
use App\Entity\Owner;
use App\Form\OwnerType;
use App\Repository\OwnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/owners')]
class OwnerController extends AbstractController {
    #[Route('', name: 'app_owner_index', methods: ['GET'])]
    public function index(Request $request, OwnerRepository $repo, PaginatorInterface $paginator): Response {
        $q = trim($request->query->get('q','')); $qb = $repo->createQueryBuilder('o')->orderBy('o.id','DESC');
        if ($q) { $qb->where('o.nombre LIKE :q OR o.rut LIKE :q')->setParameter('q','%'.$q.'%'); }
        $pagination = $paginator->paginate($qb, $request->query->getInt('page',1), 12);
        return $this->render('owner/index.html.twig',['pagination'=>$pagination,'q'=>$q]);
    }
    #[Route('/new', name: 'app_owner_new', methods: ['GET','POST'])] public function new(Request $request, EntityManagerInterface $em): Response { $o=new Owner(); $f=$this->createForm(OwnerType::class,$o); $f->handleRequest($request); if($f->isSubmitted()&&$f->isValid()){$em->persist($o);$em->flush();return $this->redirectToRoute('app_owner_index');} return $this->render('owner/new.html.twig',['form'=>$f]); }
    #[Route('/{id}', name: 'app_owner_show', methods: ['GET'])] public function show(Owner $o): Response { return $this->render('owner/show.html.twig',['owner'=>$o]); }
    #[Route('/{id}/edit', name: 'app_owner_edit', methods: ['GET','POST'])] public function edit(Request $request, Owner $o, EntityManagerInterface $em): Response { $f=$this->createForm(OwnerType::class,$o); $f->handleRequest($request); if($f->isSubmitted()&&$f->isValid()){$em->flush();return $this->redirectToRoute('app_owner_index');} return $this->render('owner/edit.html.twig',['form'=>$f,'owner'=>$o]); }
    #[Route('/{id}', name: 'app_owner_delete', methods: ['POST'])] public function delete(Request $request, Owner $o, EntityManagerInterface $em): Response { if($this->isCsrfTokenValid('delete'.$o->getId(),$request->request->get('_token'))){$em->remove($o);$em->flush();} return $this->redirectToRoute('app_owner_index'); }
}
