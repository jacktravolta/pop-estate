<?php
namespace App\Controller\Api;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Service\RutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/companies', name: 'api_company_')]
class ApiCompanyController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CompanyRepository $repo): JsonResponse
    {
        $companies = $repo->findActive();
        $data = array_map(fn(Company $c) => $this->serialize($c), $companies);
        return new JsonResponse(['data' => $data, 'total' => count($data)]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Company $company): JsonResponse
    {
        return new JsonResponse($this->serialize($company));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, RutService $rutService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['rut']) || empty($data['razonSocial']) || empty($data['direccion'])) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios: rut, razonSocial, direccion'], 400);
        }

        if (!$rutService->validate($data['rut'])) {
            return new JsonResponse(['error' => 'RUT inválido'], 400);
        }

        $company = new Company();
        $company->setRut($rutService->format($data['rut']));
        $company->setRazonSocial($data['razonSocial']);
        $company->setDireccion($data['direccion']);
        $company->setGiro($data['giro'] ?? null);
        $company->setComuna($data['comuna'] ?? null);
        $company->setCiudad($data['ciudad'] ?? null);
        $company->setEmail($data['email'] ?? null);
        $company->setCreatedBy($this->getUser());

        $em->persist($company);
        $em->flush();

        return new JsonResponse($this->serialize($company), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Company $company, EntityManagerInterface $em, RutService $rutService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['rut'])) {
            if (!$rutService->validate($data['rut'])) {
                return new JsonResponse(['error' => 'RUT inválido'], 400);
            }
            $company->setRut($rutService->format($data['rut']));
        }
        if (isset($data['razonSocial'])) $company->setRazonSocial($data['razonSocial']);
        if (isset($data['direccion'])) $company->setDireccion($data['direccion']);
        if (array_key_exists('giro', $data)) $company->setGiro($data['giro']);
        if (array_key_exists('comuna', $data)) $company->setComuna($data['comuna']);
        if (array_key_exists('ciudad', $data)) $company->setCiudad($data['ciudad']);
        if (array_key_exists('email', $data)) $company->setEmail($data['email']);
        $company->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return new JsonResponse($this->serialize($company));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Company $company, CompanyRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        if ($repo->hasActiveProperties($company)) {
            return new JsonResponse(['error' => 'No se puede eliminar: tiene propiedades activas'], 400);
        }
        $company->softDelete();
        $em->flush();
        return new JsonResponse(['message' => 'Empresa eliminada (soft delete)']);
    }

    private function serialize(Company $c): array
    {
        return [
            'id' => $c->getId(),
            'rut' => $c->getRut(),
            'razonSocial' => $c->getRazonSocial(),
            'giro' => $c->getGiro(),
            'direccion' => $c->getDireccion(),
            'comuna' => $c->getComuna(),
            'ciudad' => $c->getCiudad(),
            'email' => $c->getEmail(),
            'createdAt' => $c->getCreatedAt()?->format('c'),
            'updatedAt' => $c->getUpdatedAt()?->format('c'),
        ];
    }
}