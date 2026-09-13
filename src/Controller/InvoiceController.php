<?php
namespace App\Controller;

use App\Entity\Invoice;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices')]
class InvoiceController extends AbstractController
{
    #[Route('', name: 'app_invoice_index', methods: ['GET'])]
    public function index(Request $request, InvoiceRepository $repo): Response
    {
        $q = trim($request->query->get('q',''));
        $estado = $request->query->get('estado','');
        $page = max(1, $request->query->getInt('page',1));
        $perPage = 10; // req #3 paginador lila 10 por página

        $kpi = $repo->getKpiData();
        $qb = $repo->createFilteredQueryBuilder($q ?: null, $estado ?: null);

        $total = count($qb->getQuery()->getResult());
        $totalPages = max(1, (int)ceil($total/$perPage));
        $invoices = $qb->setFirstResult(($page-1)*$perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return $this->render('invoice/index.html.twig', [
            'invoices' => $invoices,
            'pagination' => $invoices, // compatibilidad con tu index viejo que usa pagination
            'kpi' => $kpi,
            'q' => $q,
            'estadoFilter' => $estado,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalResults' => $total,
            'form' => $this->createForm(InvoiceType::class, new Invoice())->createView(),
        ]);
    }

    #[Route('/new', name: 'app_invoice_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $invoice = new Invoice();
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // validación periodo+folio único salvo ANULADA
            if ($invoice->getPeriodo()) {
                $exists = $em->getRepository(Invoice::class)->createQueryBuilder('i')
                    ->where('i.periodo = :per')->andWhere('i.folio = :folio')->andWhere('i.estado != :anulada')
                    ->setParameter('per',$invoice->getPeriodo())->setParameter('folio',$invoice->getFolio())
                    ->setParameter('anulada','ANULADA')->getQuery()->getOneOrNullResult();
                if ($exists) {
                    $this->addFlash('danger','Ya existe facturación para periodo '.$invoice->getPeriodo().' y folio '.$invoice->getFolio());
                    return $this->render('invoice/new.html.twig',['form'=>$form]);
                }
            }
            $invoice->setCreatedBy($this->getUser());
            $invoice->setCreatedAt(new \DateTimeImmutable());
            $em->persist($invoice); $em->flush();
            $this->addFlash('success','Factura #'.$invoice->getId().' creada');
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
        // Req #2: PAGADA y ANULADA no editables
        if (in_array(strtoupper($invoice->getEstado()), ['PAGADA','ANULADA'])) {
            $this->addFlash('warning','No se puede editar factura en estado '.$invoice->getEstado().' - FINAL');
            return $this->redirectToRoute('app_invoice_index');
        }
        $form = $this->createForm(InvoiceType::class, $invoice); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $em->flush(); $this->addFlash('success','Factura actualizada'); return $this->redirectToRoute('app_invoice_index'); }
        return $this->render('invoice/edit.html.twig',['form'=>$form,'invoice'=>$invoice]);
    }

    // Req #2: eliminar -> ANULADA con observación auditoría, no hard delete. PAGADA no se puede eliminar
    #[Route('/{id}', name: 'app_invoice_delete', methods: ['POST'])]
    public function delete(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        if (strtoupper($invoice->getEstado()) === 'PAGADA') {
            $this->addFlash('danger','No se puede eliminar factura PAGADA - estado FINAL');
            return $this->redirectToRoute('app_invoice_index');
        }
        if (!$this->isCsrfTokenValid('delete'.$invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger','CSRF inválido');
            return $this->redirectToRoute('app_invoice_index');
        }
        $motivo = trim($request->request->get('motivo',''));
        if (strlen($motivo) < 10) {
            $this->addFlash('danger','Observación mínima 10 caracteres para anular');
            return $this->redirectToRoute('app_invoice_index');
        }
        $invoice->setEstado('ANULADA');
        $invoice->setObservacion($motivo.' | Anulada por '.$this->getUser()?->getUserIdentifier().' el '.(new \DateTime())->format('d/m/Y H:i'));
        $em->flush();
        $this->addFlash('success','Factura #'.$invoice->getId().' ANULADA: '.$motivo);
        return $this->redirectToRoute('app_invoice_index');
    }

    #[Route('/{id}/emit', name: 'app_invoice_emit', methods: ['POST'])]
    public function emit(Request $request, Invoice $invoice, EntityManagerInterface $em): Response {
        if (!$this->isCsrfTokenValid('emit'.$invoice->getId(), $request->request->get('_token'))) return $this->redirectToRoute('app_invoice_index');
        if (strtoupper($invoice->getEstado()) !== 'PENDIENTE') {
            $this->addFlash('danger','Solo PENDIENTE puede pasar a EMITIDA');
        } else {
            $invoice->setEstado('EMITIDA'); $em->flush(); $this->addFlash('success','Factura EMITIDA - puede pagarse');
        }
        return $this->redirectToRoute('app_invoice_index');
    }

    #[Route('/{id}/pay', name: 'app_invoice_pay', methods: ['POST'])]
    public function pay(Request $request, Invoice $invoice, EntityManagerInterface $em): Response {
        if (!$this->isCsrfTokenValid('pay'.$invoice->getId(), $request->request->get('_token'))) return $this->redirectToRoute('app_invoice_index');
        $est = strtoupper($invoice->getEstado());
        if (!in_array($est, ['EMITIDA','PENDIENTE','VENCIDA'])) {
            $this->addFlash('danger','No se puede pagar en estado '.$est);
        } else {
            $invoice->setEstado('PAGADA'); $em->flush(); $this->addFlash('success','Factura PAGADA - FINAL, no editable');
        }
        return $this->redirectToRoute('app_invoice_index');
    }

    #[Route('/{id}/anular', name: 'app_invoice_anular', methods: ['POST'])]
    public function anular(Request $request, Invoice $invoice, EntityManagerInterface $em): Response {
        if (!$this->isCsrfTokenValid('anular'.$invoice->getId(), $request->request->get('_token'))) return $this->redirectToRoute('app_invoice_index');
        if (strtoupper($invoice->getEstado()) === 'PAGADA') {
            $this->addFlash('danger','No se puede anular factura PAGADA - FINAL');
        } else {
            $motivo = trim($request->request->get('motivo','Anulación desde acción rápida'));
            $invoice->setEstado('ANULADA');
            $invoice->setObservacion($motivo.' | '.$this->getUser()?->getUserIdentifier());
            $em->flush(); $this->addFlash('warning','Factura ANULADA');
        }
        return $this->redirectToRoute('app_invoice_index');
    }
}
