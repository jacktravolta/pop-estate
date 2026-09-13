<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.deletedAt IS NULL')
            ->orderBy('c.razonSocial', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByRut(string $rut): ?Company
    {
        return $this->createQueryBuilder('c')
            ->where('c.rut = :rut')
            ->setParameter('rut', $rut)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasActiveProperties(Company $company): bool
    {
        $count = $this->getEntityManager()
            ->createQuery('SELECT COUNT(p.id) FROM App\Entity\Property p WHERE p.company = :company AND p.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->getSingleScalarResult();
        
        return $count > 0;
    }
}