<?php

namespace App\Repository;

use App\Entity\Owner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Owner::class);
    }

    public function findByRut(string $rut): ?Owner
    {
        return $this->createQueryBuilder('o')
            ->where('o.rut = :rut')
            ->setParameter('rut', $rut)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}