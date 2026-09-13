<?php
namespace App\Controller\Api;

use App\Entity\Invoice;
use App\Entity\Settlement;
use App\Repository\InvoiceRepository;
use App\Service\BillingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/invoices', name: 'api_invoice_')]
class ApiInvoiceController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(InvoiceRepository $repo): JsonResponse
    {
        $invoices = $repo->findBy([], ['fecha' => 'DESC']);
        $data = array_map(fn(Invoice $i) => $this->serialize($i), $invoices);
        return new JsonResponse(['data' => $data, 'total' => count($data)]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Invoice $invoice): JsonResponse
    {
        return new JsonResponse($this->serialize($invoice, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, BillingService $billingService, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['folio']) || empty($data['emisor']) || empty($data['rutEmisor']) 
            || empty($data['fecha']) || empty($data['periodo']) || empty($data['settlement_ids'])) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios'], 400);
        }

        $settlements = [];
        foreach ($data['settlement_ids'] as $id) {
            $s = $em->find(Settlement::class, $id);
            if ($s) $settlements[] = $s;
        }

        try {
            $invoice = $billingService->createInvoice(
                (int) $data['folio'],
                $data['emisor'],
                $data['rutEmisor'],
                new \DateTime($data['fecha']),
                $data['periodo'],
                $settlements
            );
            return new JsonResponse($this->serialize($invoice, true), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function serialize(Invoice $i, bool $withSettlements = false): array
    {
        $data = [
            'id' => $i->getId(),
            'folio' => $i->getFolio(),
            'emisor' => $i->getEmisor(),
            'rutEmisor' => $i->getRutEmisor(),
            'fecha' => $i->getFecha()?->format('Y-m-d'),
            'periodo' => $i->getPeriodo(),
            'estado' => $i->getEstado(),
            'totales' => [
                'neto' => $i->getNeto(),
                'iva' => $i->getIva(),
                'total' => $i->getTotal(),
            ],
            'createdAt' => $i->getCreatedAt()?->format('c'),
        ];

        if ($withSettlements) {
            $data['settlements'] = array_map(fn($is) => [
                'id' => $is->getSettlement()->getId(),
                'direccion' => $is->getSettlement()->getProperty()->getDireccion(),
                'total' => $is->getSettlement()->getTotal(),
            ], $i->getInvoiceSettlements()->toArray());
        }

        return $data;
    }
}