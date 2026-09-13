<?php
namespace App\Service;

use App\Entity\Invoice;
use App\Repository\SettlementRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Servicio cruce de datos por periodos - req Pop Estate
 * Cruza Settlement(PAGADA) + Property + Periodo -> Invoice
 * Documentado y legible - theme lila compatible
 */
class InvoicePeriodGenerator
{
    public function __construct(
        private SettlementRepository $settlementRepo,
        private InvoiceRepository $invoiceRepo,
        private EntityManagerInterface $em
    ) {}

    /**
     * Preview sin guardar - para buscador tiempo real
     * @return array{total: float, count: int, settlements: array, breakdown: array}
     */
    public function preview(\DateTimeInterface $inicio, \DateTimeInterface $fin,?int $propertyId = null): array
    {
        $qb = $this->settlementRepo->createQueryBuilder('s')
            ->leftJoin('s.property','p')->addSelect('p')
            ->andWhere('s.fechaInicio >= :inicio')->andWhere('s.fechaTermino <= :fin')
            ->andWhere('s.estado = :estado') // solo liquidaciones PAGADAS se facturan
            ->setParameter('inicio', $inicio)
            ->setParameter('fin', $fin)
            ->setParameter('estado', 'PAGADA')
            ->orderBy('s.id','DESC');

        if ($propertyId) {
            $qb->andWhere('p.id = :prop')->setParameter('prop', $propertyId);
        }

        $settlements = $qb->getQuery()->getResult();
        $total = array_sum(array_map(fn($s) => (float)$s->getTotal(), $settlements));

        // breakdown por propiedad para KPI
        $breakdown = [];
        foreach ($settlements as $s) {
            $dir = $s->getProperty()?->getDireccion()?? 'Sin propiedad';
            $breakdown[$dir] = ($breakdown[$dir]?? 0) + (float)$s->getTotal();
        }

        return [
            'total' => $total,
            'count' => count($settlements),
            'settlements' => $settlements,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Genera facturas por periodo - crea 1 invoice por propiedad o 1 global
     * @return Invoice[]
     */
    public function generate(\DateTimeInterface $inicio, \DateTimeInterface $fin,?int $propertyId, string $emisor,?string $receptor, \App\Entity\User $user): array
    {
        $preview = $this->preview($inicio, $fin, $propertyId);
        if ($preview['count'] === 0) {
            throw new \RuntimeException('No hay liquidaciones PAGADAS en periodo '.$inicio->format('d/m/Y').' - '.$fin->format('d/m/Y'));
        }

        $periodoStr = $inicio->format('m/Y').' - '.$fin->format('m/Y');
        $created = [];

        // Si hay propertyId -> 1 factura. Si no -> 1 factura por propiedad (cruce real)
        $grouped = [];
        if ($propertyId) {
            $grouped['single'] = $preview['settlements'];
        } else {
            foreach ($preview['settlements'] as $s) {
                $pid = $s->getProperty()?->getId()?? 0;
                $grouped[$pid][] = $s;
            }
        }

        foreach ($grouped as $propId => $setts) {
            $totalGroup = array_sum(array_map(fn($s) => (float)$s->getTotal(), $setts));
            $folio = sprintf('FAC-%s-%s-%04d', $inicio->format('Ym'), $propId?: 'GLOBAL', time() % 10000);

            // validación duplicado periodo+folio
            $exists = $this->invoiceRepo->createQueryBuilder('i')
                ->where('i.periodo = :per')->andWhere('i.folio = :folio')->andWhere('i.estado!= :anulada')
                ->setParameter('per', $periodoStr)->setParameter('folio', $folio)->setParameter('anulada','ANULADA')
                ->getQuery()->getOneOrNullResult();
            if ($exists) continue;

            $invoice = new Invoice();
            $invoice->setFolio($folio);
            $invoice->setEmisor($emisor);
            $invoice->setReceptor($receptor?: ($setts[0]->getProperty()?->getDireccion()?? 'Varios'));
            $invoice->setPeriodo($periodoStr);
            $invoice->setTotal($totalGroup);
            $invoice->setEstado('PENDIENTE');
            $invoice->setObservacion(sprintf('Generada por periodo %s al %s - %d liquidaciones cruzadas - Total $%s - Prop %s',
                $inicio->format('d/m/Y'), $fin->format('d/m/Y'), count($setts),
                number_format($totalGroup,0,',','.'), $propId
            ));
            $invoice->setCreatedBy($user);
            $invoice->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($invoice);
            $created[] = $invoice;
        }

        $this->em->flush();
        return $created;
    }
}
