<?php
namespace App\Controller;
use App\Entity\Invoice;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices')]
class InvoiceController extends AbstractController
{
    #[Route('', name: 'app_invoice_index', methods: ['GET'])]
    public function index(Request $request, InvoiceRepository $repo, PaginatorInterface $paginator): Response
    {
        $q = trim($request->query->get('q','')); $qb=$repo->createQueryBuilder('i')->orderBy('i.id','DESC');
        if($q){$qb->where('i.folio LIKE :q OR i.emisor LIKE :q OR i.receptor LIKE :q OR i.periodo LIKE :q OR i.estado LIKE :q')->setParameter('q','%'.$q.'%');}
        $pagination=$paginator->paginate($qb,$request->query->getInt('page',1),12);
        return $this->render('invoice/index.html.twig',['pagination'=>$pagination,'q'=>$q]);
    }
    #[Route('/new', name: 'app_invoice_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $invoice = new Invoice(); $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($invoice->getPeriodo()) {
                $exists = $em->getRepository(Invoice::class)->createQueryBuilder('i')
                    ->where('i.periodo = :per')->andWhere('i.folio = :folio')->andWhere('i.estado != :anulada')
                    ->setParameter('per',$invoice->getPeriodo())->setParameter('folio',$invoice->getFolio())
                    ->setParameter('anulada','ANULADA')->getQuery()->getOneOrNullResult();
                if ($exists) {
                    $this->addFlash('danger','Ya existe facturacion para periodo '.$invoice->getPeriodo().' y folio '.$invoice->getFolio());
                    return $this->render('invoice/new.html.twig',['form'=>$form]);
                }
            }
            $em->persist($invoice); $em->flush();
            if ($request->isXmlHttpRequest()) return new Response('OK');
            return $this->redirectToRoute('app_invoice_index');
        }
        return $this->render('invoice/new.html.twig',['form'=>$form]);
    }
    #[Route('/{id}', name: 'app_invoice_show', methods: ['GET'])]
    public function show(Invoice $invoice): Response { return $this->render('invoice/show.html.twig',['invoice'=>$invoice]); }
    #[Route('/{id}/print', name: 'app_invoice_print', methods: ['GET'])]
    public function printView(Invoice $invoice): Response { return $this->render('invoice/print.html.twig',['invoice'=>$invoice]); }
    #[Route('/{id}/pdf', name: 'app_invoice_pdf', methods: ['GET'])]
    public function pdf(Invoice $invoice): Response { return $this->render('invoice/print.html.twig',['invoice'=>$invoice]); }
    #[Route('/{id}/edit', name: 'app_invoice_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        if (in_array(strtoupper($invoice->getEstado()), ['PAGADA','ANULADA'])) {
            $this->addFlash('warning','No se puede editar factura en estado '.$invoice->getEstado());
            return $this->redirectToRoute('app_invoice_index');
        }
        $form = $this->createForm(InvoiceType::class, $invoice); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $em->flush(); if ($request->isXmlHttpRequest()) return new Response('OK'); return $this->redirectToRoute('app_invoice_index'); }
        return $this->render('invoice/edit.html.twig',['form'=>$form,'invoice'=>$invoice]);
    }
    #[Route('/{id}', name: 'app_invoice_delete', methods: ['POST'])]
    public function delete(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        if (in_array(strtoupper($invoice->getEstado()), ['PAGADA'])) {
            $this->addFlash('danger','No se puede eliminar factura PAGADA');
            return $this->redirectToRoute('app_invoice_index');
        }
        if ($this->isCsrfTokenValid('delete'.$invoice->getId(), $request->request->get('_token'))) { $em->remove($invoice); $em->flush(); }
        return $this->redirectToRoute('app_invoice_index');
    }
    #[Route('/{id}/emit', name: 'app_invoice_emit', methods: ['POST'])]
    public function emit(Invoice $invoice, EntityManagerInterface $em): Response {
        if (strtoupper($invoice->getEstado()) !== 'PENDIENTE') {
            $this->addFlash('danger','Solo PENDIENTE puede pasar a EMITIDA');
        } else {
            $invoice->setEstado('EMITIDA'); $em->flush(); $this->addFlash('success','Factura EMITIDA');
        }
        return $this->redirectToRoute('app_invoice_index');
    }
    #[Route('/{id}/pay', name: 'app_invoice_pay', methods: ['POST'])]
    public function pay(Invoice $invoice, EntityManagerInterface $em): Response {
        $est = strtoupper($invoice->getEstado());
        if (!in_array($est, ['EMITIDA','PENDIENTE','VENCIDA'])) {
            $this->addFlash('danger','No se puede pagar en estado '.$est);
        } else {
            $invoice->setEstado('PAGADA'); $em->flush(); $this->addFlash('success','Factura PAGADA - final');
        }
        return $this->redirectToRoute('app_invoice_index');
    }
    #[Route('/{id}/anular', name: 'app_invoice_anular', methods: ['POST'])]
    public function anular(Invoice $invoice, EntityManagerInterface $em): Response {
        if (strtoupper($invoice->getEstado()) === 'PAGADA') {
            $this->addFlash('danger','No se puede anular factura PAGADA');
        } else {
            $invoice->setEstado('ANULADA'); $em->flush(); $this->addFlash('warning','Factura ANULADA');
        }
        return $this->redirectToRoute('app_invoice_index');
    }
}
