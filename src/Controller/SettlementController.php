<?php
namespace App\Controller;

use App\Entity\Settlement;
use App\Form\SettlementType;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settlements')]
class SettlementController extends AbstractController
{
    #[Route('/', name: 'app_settlement_index', methods: ['GET'])]
    public function index(Request $request, SettlementRepository $repo): Response
    {
        $q = trim($request->query->get('q',''));
        $estado = $request->query->get('estado','');
        $page = max(1, (int)$request->query->get('page',1));
        $perPage = 10;

        $kpi = $repo->getKpiData();
        $qb = $repo->createFilteredQueryBuilder($q ?: null, $estado ?: null);
        $total = count($qb->getQuery()->getResult());
        $totalPages = max(1, (int)ceil($total/$perPage));
        $settlements = $qb->setFirstResult(($page-1)*$perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return $this->render('settlement/index.html.twig', [
            'settlements' => $settlements,
            'form' => $this->createForm(SettlementType::class, new Settlement())->createView(),
            'kpi' => $kpi,
            'q' => $q,
            'estadoFilter' => $estado,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalResults' => $total,
        ]);
    }

    #[Route('/new', name: 'app_settlement_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $settlement = new Settlement();
        $form = $this->createForm(SettlementType::class, $settlement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $settlement->setEstado('PENDIENTE');
            $settlement->setCreatedAt(new \DateTimeImmutable());
            $settlement->setCreatedBy($this->getUser());
            $this->recalcular($settlement);
            $em->persist($settlement);
            $em->flush();
            $this->addFlash('success','Liquidación #'.$settlement->getId().' creada');
            return $this->redirectToRoute('app_settlement_index');
        }
        return $this->render('settlement/index.html.twig', [
            'settlements' => $em->getRepository(Settlement::class)->findBy([], ['id'=>'DESC']),
            'form' => $form->createView(),
            'kpi' => $em->getRepository(Settlement::class)->getKpiData(),
            'q' => '', 'estadoFilter' => '', 'page' => 1, 'totalPages' => 1, 'totalResults' => 0
        ]);
    }

    #[Route('/{id}', name: 'app_settlement_show', methods: ['GET'])]
    public function show(Settlement $s): Response {
        return $this->render('settlement/show.html.twig', ['settlement'=>$s]);
    }

    #[Route('/{id}/edit', name: 'app_settlement_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Settlement $s, EntityManagerInterface $em): Response
    {
        if ($s->getEstado() !== 'PENDIENTE') {
            $this->addFlash('warning','Solo PENDIENTE editable. Estado: '.$s->getEstado());
            return $this->redirectToRoute('app_settlement_index');
        }
        $form = $this->createForm(SettlementType::class, $s);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->recalcular($s);
            $em->flush();
            $this->addFlash('success','Actualizada');
            return $this->redirectToRoute('app_settlement_index');
        }
        return $this->render('settlement/edit.html.twig', ['settlement'=>$s,'form'=>$form->createView()]);
    }

    #[Route('/{id}/delete', name: 'app_settlement_delete', methods: ['POST'])]
    public function delete(Request $request, Settlement $s, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$s->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger','CSRF inválido');
            return $this->redirectToRoute('app_settlement_index');
        }
        $motivo = trim($request->request->get('motivo',''));
        if (strlen($motivo) < 10) {
            $this->addFlash('danger','Observación mínima 10 caracteres para anular');
            return $this->redirectToRoute('app_settlement_index');
        }
        // Req #2: No hard delete, pasa a ANULADA con auditoría observacion + fecha + usuario
        $s->setEstado('ANULADA');
        $s->setObservacion($motivo);
        $em->flush();
        $this->addFlash('success','Liquidación #'.$s->getId().' ANULADA: '.$motivo);
        return $this->redirectToRoute('app_settlement_index');
    }

    #[Route('/{id}/pay', name: 'app_settlement_pay', methods: ['POST'])]
    public function pay(Request $request, Settlement $s, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('pay'.$s->getId(), $request->request->get('_token'))) {
            if ($s->getEstado() !== 'ANULADA') {
                $s->setEstado($s->getEstado() === 'PAGADA' ? 'PENDIENTE' : 'PAGADA');
                $em->flush();
            }
        }
        return $this->redirectToRoute('app_settlement_index');
    }

    #[Route('/{id}/pdf', name: 'app_settlement_pdf', methods: ['GET'])]
    public function pdf(Settlement $s): Response {
        return $this->render('settlement/pdf.html.twig', ['settlement'=>$s]);
    }

    private function recalcular(Settlement $s): void
    {
        $cargo = 0; $desc = 0;
        foreach ($s->getItems() as $item) {
            $item->setSettlement($s);
            if ($item->getTipo() === 'DESCUENTO') $desc += abs((float)$item->getMonto());
            else $cargo += abs((float)$item->getMonto());
        }
        $neto = $cargo - $desc;
        $iva = round($neto * 0.19);
        $total = $neto + $iva;
        $s->setTotalCargo((string)$cargo);
        $s->setTotalDescuento((string)$desc);
        $s->setTotalNeto((string)$neto);
        $s->setIva((string)$iva);
        $s->setTotal((string)$total);
    }
}
