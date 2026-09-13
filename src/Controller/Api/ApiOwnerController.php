<?php
namespace App\Controller\Api;

use App\Entity\Owner;
use App\Repository\OwnerRepository;
use App\Service\RutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/owners', name: 'api_owner_')]
class ApiOwnerController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(OwnerRepository $repo): JsonResponse
    {
        $owners = $repo->findAllOrdered();
        $data = array_map(fn(Owner $o) => $this->serialize($o), $owners);
        return new JsonResponse(['data' => $data, 'total' => count($data)]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Owner $owner): JsonResponse
    {
        return new JsonResponse($this->serialize($owner));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, RutService $rutService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['rut']) || empty($data['nombre'])) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios: rut, nombre'], 400);
        }

        if (!$rutService->validate($data['rut'])) {
            return new JsonResponse(['error' => 'RUT inválido'], 400);
        }

        $owner = new Owner();
        $owner->setRut($rutService->format($data['rut']));
        $owner->setNombre($data['nombre']);
        $owner->setGiro($data['giro'] ?? null);
        $owner->setDireccion($data['direccion'] ?? null);
        $owner->setComuna($data['comuna'] ?? null);
        $owner->setCiudad($data['ciudad'] ?? null);
        $owner->setEmail($data['email'] ?? null);

        $em->persist($owner);
        $em->flush();

        return new JsonResponse($this->serialize($owner), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Owner $owner, EntityManagerInterface $em, RutService $rutService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['rut'])) {
            if (!$rutService->validate($data['rut'])) {
                return new JsonResponse(['error' => 'RUT inválido'], 400);
            }
            $owner->setRut($rutService->format($data['rut']));
        }
        if (isset($data['nombre'])) $owner->setNombre($data['nombre']);
        if (array_key_exists('giro', $data)) $owner->setGiro($data['giro']);
        if (array_key_exists('direccion', $data)) $owner->setDireccion($data['direccion']);
        if (array_key_exists('comuna', $data)) $owner->setComuna($data['comuna']);
        if (array_key_exists('ciudad', $data)) $owner->setCiudad($data['ciudad']);
        if (array_key_exists('email', $data)) $owner->setEmail($data['email']);

        $em->flush();

        return new JsonResponse($this->serialize($owner));
    }

    private function serialize(Owner $o): array
    {
        return [
            'id' => $o->getId(),
            'rut' => $o->getRut(),
            'nombre' => $o->getNombre(),
            'giro' => $o->getGiro(),
            'direccion' => $o->getDireccion(),
            'comuna' => $o->getComuna(),
            'ciudad' => $o->getCiudad(),
            'email' => $o->getEmail(),
            'createdAt' => $o->getCreatedAt()?->format('c'),
        ];
    }
}