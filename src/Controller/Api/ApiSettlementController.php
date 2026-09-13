<?php
namespace App\Controller\Api;

use App\Entity\Property;
use App\Entity\Settlement;
use App\Entity\SettlementItem;
use App\Repository\SettlementRepository;
use App\Service\SettlementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/settlements', name: 'api_settlement_')]
class ApiSettlementController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(SettlementRepository $repo): JsonResponse
    {
        $settlements = $repo->findBy([], ['fechaInicio' => 'DESC']);
        $data = array_map(fn(Settlement $s) => $this->serialize($s), $settlements);
        return new JsonResponse(['data' => $data, 'total' => count($data)]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Settlement $settlement): JsonResponse
    {
        return new JsonResponse($this->serialize($settlement, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SettlementService $service): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['property_id']) || empty($data['fechaInicio']) || empty($data['fechaTermino'])) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios'], 400);
        }

        $property = $em->find(Property::class, $data['property_id']);
        if (!$property) {
            return new JsonResponse(['error' => 'Propiedad no encontrada'], 404);
        }

        $settlement = new Settlement();
        $settlement->setProperty($property);
        $settlement->setFechaInicio(new \DateTime($data['fechaInicio']));
        $settlement->setFechaTermino(new \DateTime($data['fechaTermino']));
        $settlement->setCreatedBy($this->getUser());

        foreach ($data['items'] ?? [] as $itemData) {
            if (!in_array($itemData['tipo'] ?? '', ['CARGO', 'DESCUENTO'])) continue;
            $item = new SettlementItem();
            $item->setTipo($itemData['tipo']);
            $item->setDescripcion($itemData['descripcion'] ?? '');
            $item->setMonto((string) ($itemData['monto'] ?? '0'));
            $settlement->addItem($item);
        }

        try {
            $service->save($settlement);
            return new JsonResponse($this->serialize($settlement, true), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/pay', name: 'pay', methods: ['POST'])]
    public function pay(Settlement $settlement, SettlementService $service): JsonResponse
    {
        try {
            $service->markAsPaid($settlement);
            return new JsonResponse($this->serialize($settlement));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function serialize(Settlement $s, bool $withItems = false): array
    {
        $data = [
            'id' => $s->getId(),
            'estado' => $s->getEstado(),
            'fechaInicio' => $s->getFechaInicio()?->format('Y-m-d'),
            'fechaTermino' => $s->getFechaTermino()?->format('Y-m-d'),
            'property' => [
                'id' => $s->getProperty()->getId(),
                'direccion' => $s->getProperty()->getDireccion(),
            ],
            'totales' => [
                'cargo' => $s->getTotalCargo(),
                'descuento' => $s->getTotalDescuento(),
                'neto' => $s->getTotalNeto(),
                'iva' => $s->getIva(),
                'total' => $s->getTotal(),
            ],
            'createdAt' => $s->getCreatedAt()?->format('c'),
        ];

        if ($withItems) {
            $data['items'] = array_map(fn($i) => [
                'id' => $i->getId(),
                'tipo' => $i->getTipo(),
                'descripcion' => $i->getDescripcion(),
                'monto' => $i->getMonto(),
            ], $s->getItems()->toArray());
        }

        return $data;
    }
}