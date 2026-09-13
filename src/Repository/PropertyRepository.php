<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.direccion', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.company = :companyId')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('companyId', $companyId)
            ->orderBy('p.direccion', 'ASC')
            ->getQuery()
            ->getResult();
    }
}