<?php
namespace App\Repository;

use App\Entity\Settlement;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class SettlementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settlement::class);
    }

    public function createFilteredQueryBuilder(?string $q, ?string $estado): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.property', 'p')
            ->addSelect('p')
            ->orderBy('s.id', 'DESC');
        if ($q) {
            if (is_numeric($q)) {
                $qb->andWhere('s.id = :idExact OR p.direccion LIKE :q OR s.estado LIKE :q OR s.observacion LIKE :q')
                   ->setParameter('idExact', (int)$q)
                   ->setParameter('q', '%'.$q.'%');
            } else {
                $qb->andWhere('p.direccion LIKE :q OR s.estado LIKE :q OR s.observacion LIKE :q')
                   ->setParameter('q', '%'.$q.'%');
            }
        }
        if ($estado) {
            $qb->andWhere('s.estado = :estado')->setParameter('estado', $estado);
        }
        return $qb;
    }

    public function getKpiData(): array
    {
        $all = $this->createQueryBuilder('s')
            ->select('s.estado, s.total')->getQuery()->getResult();
        $countBy = function(string $estado) use ($all): int {
            return count(array_filter($all, fn($r) => $r['estado'] === $estado));
        };
        $sumBy = function(string $estado) use ($all): float {
            return array_sum(array_map(fn($r) => $r['estado'] === $estado ? (float)$r['total'] : 0, $all));
        };
        return [
            'total' => count($all),
            'totalMonto' => array_sum(array_map(fn($r) => (float)$r['total'], $all)),
            'pendientesCount' => $countBy('PENDIENTE'),
            'pendientesMonto' => $sumBy('PENDIENTE'),
            'pagadasCount' => $countBy('PAGADA'),
            'pagadasMonto' => $sumBy('PAGADA'),
            'anuladasCount' => $countBy('ANULADA'),
            'anuladasMonto' => $sumBy('ANULADA'),
        ];
    }

    public function getKpiReal(): array { return $this->getKpiData(); }

    // NUEVO: detecta duplicado mismo periodo misma propiedad (excluye ANULADAS)
    public function findDuplicate(Property $property, \DateTimeInterface $inicio, \DateTimeInterface $termino, ?int $excludeId = null): ?Settlement
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.property = :prop')
            ->andWhere('s.fechaInicio = :ini')
            ->andWhere('s.fechaTermino = :fin')
            ->andWhere('s.estado != :anulada')
            ->setParameter('prop', $property)
            ->setParameter('ini', $inicio->format('Y-m-d'))
            ->setParameter('fin', $termino->format('Y-m-d'))
            ->setParameter('anulada', 'ANULADA')
            ->setMaxResults(1);
        if ($excludeId) {
            $qb->andWhere('s.id != :ex')->setParameter('ex', $excludeId);
        }
        return $qb->getQuery()->getOneOrNullResult();
    }
}
