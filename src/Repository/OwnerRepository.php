<?php
namespace App\Repository;
use App\Entity\Owner;
use App\Validator\RutHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class OwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry){ parent::__construct($registry, Owner::class); }

    public function createFilteredQueryBuilder(?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')->orderBy('o.id','DESC');
        if ($q) {
            $qb->andWhere('o.rut LIKE :q OR o.nombre LIKE :q OR o.email LIKE :q OR o.comuna LIKE :q')->setParameter('q','%'.$q.'%');
        }
        return $qb;
    }

    public function getKpiData(): array
    {
        $all = $this->createQueryBuilder('o')->select('o.id, o.email, o.createdAt')->getQuery()->getResult();
        $total = count($all);
        $conEmail = count(array_filter($all, fn($r)=>!empty($r['email'])));
        $sinEmail = $total-$conEmail;
        $inicioMes = new \DateTime('first day of this month');
        $esteMes = count(array_filter($all, function($r) use($inicioMes){ $f=$r['createdAt']; return $f instanceof \DateTimeInterface && $f >= $inicioMes; }));
        $conn = $this->getEntityManager()->getConnection();
        $conPropiedades = (int)$conn->fetchOne('SELECT COUNT(DISTINCT owner_id) FROM property WHERE owner_id IS NOT NULL AND deleted_at IS NULL');
        return ['total'=>$total,'conEmail'=>$conEmail,'sinEmail'=>$sinEmail,'esteMes'=>$esteMes,'conPropiedades'=>$conPropiedades];
    }

    public function hasActiveProperties(Owner $owner): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $count = (int)$conn->fetchOne('SELECT COUNT(*) FROM property WHERE owner_id = :id AND deleted_at IS NULL',['id'=>$owner->getId()]);
        return $count>0;
    }
    public function countPropertiesByOwner(Owner $owner): int
    {
        $conn = $this->getEntityManager()->getConnection();
        return (int)$conn->fetchOne('SELECT COUNT(*) FROM property WHERE owner_id = :id AND deleted_at IS NULL',['id'=>$owner->getId()]);
    }

    /**
     * FIX: busca por RUT limpio
     */
    public function findByRut(string $rut): ?Owner
    {
        $cleanInput = RutHelper::clean($rut);
        $all = $this->createQueryBuilder('o')->getQuery()->getResult();
        foreach ($all as $o) {
            if (RutHelper::clean($o->getRut()) === $cleanInput) return $o;
        }
        return null;
    }
}
