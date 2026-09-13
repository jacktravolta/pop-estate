<?php
namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry){ parent::__construct($registry, Invoice::class); }

    /**
     * Buscador tiempo real req #4 + filtro estado - sin CAST, usa ILIKE para Postgres
     */
    public function createFilteredQueryBuilder(?string $q, ?string $estado): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')->orderBy('i.id','DESC');
        if ($q) {
            if (is_numeric($q)) {
                $qb->andWhere('i.id = :idExact OR i.folio ILIKE :q OR i.emisor ILIKE :q OR i.receptor ILIKE :q OR i.periodo ILIKE :q OR i.estado ILIKE :q OR i.observacion ILIKE :q')
                   ->setParameter('idExact', (int)$q)
                   ->setParameter('q', '%'.$q.'%');
            } else {
                $qb->andWhere('i.folio ILIKE :q OR i.emisor ILIKE :q OR i.receptor ILIKE :q OR i.periodo ILIKE :q OR i.estado ILIKE :q OR i.observacion ILIKE :q')
                   ->setParameter('q', '%'.$q.'%');
            }
        }
        if ($estado) {
            $qb->andWhere('i.estado = :estado')->setParameter('estado', $estado);
        }
        return $qb;
    }

    /**
     * KPI con información real req #5 - usado en header CRUD
     */
    public function getKpiData(): array
    {
        $all = $this->createQueryBuilder('i')->select('i.estado, i.total')->getQuery()->getResult();
        $count = fn(string $e) => count(array_filter($all, fn($r) => strtoupper($r['estado']) === $e));
        $sum = fn(string $e) => array_sum(array_map(fn($r) => strtoupper($r['estado']) === $e ? (float)$r['total'] : 0, $all));
        return [
            'total' => count($all),
            'totalMonto' => array_sum(array_map(fn($r) => (float)$r['total'], $all)),
            'pendientesCount' => $count('PENDIENTE'),
            'pendientesMonto' => $sum('PENDIENTE'),
            'emitidasCount' => $count('EMITIDA'),
            'emitidasMonto' => $sum('EMITIDA'),
            'pagadasCount' => $count('PAGADA'),
            'pagadasMonto' => $sum('PAGADA'),
            'vencidasCount' => $count('VENCIDA'),
            'vencidasMonto' => $sum('VENCIDA'),
            'anuladasCount' => $count('ANULADA'),
            'anuladasMonto' => $sum('ANULADA'),
        ];
    }
}
