<?php
namespace App\Controller\Api;

use App\Entity\Property;
use App\Entity\Company;
use App\Entity\Owner;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/properties', name: 'api_property_')]
class ApiPropertyController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(PropertyRepository $repo): JsonResponse
    {
        $properties = $repo->findActive();
        $data = array_map(fn(Property $p) => $this->serialize($p), $properties);
        return new JsonResponse(['data' => $data, 'total' => count($data)]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Property $property): JsonResponse
    {
        return new JsonResponse($this->serialize($property));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['direccion']) || empty($data['company_id']) || empty($data['owner_id'])) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios: direccion, company_id, owner_id'], 400);
        }

        $company = $em->find(Company::class, $data['company_id']);
        $owner = $em->find(Owner::class, $data['owner_id']);

        if (!$company || !$owner) {
            return new JsonResponse(['error' => 'Empresa o propietario no encontrado'], 404);
        }

        $property = new Property();
        $property->setDireccion($data['direccion']);
        $property->setCompany($company);
        $property->setOwner($owner);
        $property->setRol($data['rol'] ?? null);
        $property->setComuna($data['comuna'] ?? null);
        $property->setCiudad($data['ciudad'] ?? null);

        $em->persist($property);
        $em->flush();

        return new JsonResponse($this->serialize($property), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Property $property, EntityManagerInterface $em): JsonResponse
    {
        $property->softDelete();
        $em->flush();
        return new JsonResponse(['message' => 'Propiedad eliminada (soft delete)']);
    }

    private function serialize(Property $p): array
    {
        return [
            'id' => $p->getId(),
            'direccion' => $p->getDireccion(),
            'rol' => $p->getRol(),
            'comuna' => $p->getComuna(),
            'ciudad' => $p->getCiudad(),
            'company' => [
                'id' => $p->getCompany()->getId(),
                'razonSocial' => $p->getCompany()->getRazonSocial(),
            ],
            'owner' => [
                'id' => $p->getOwner()->getId(),
                'nombre' => $p->getOwner()->getNombre(),
            ],
            'createdAt' => $p->getCreatedAt()?->format('c'),
        ];
    }
}