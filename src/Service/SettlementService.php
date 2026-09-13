<?php
namespace App\Service;
use App\Entity\Settlement;
use Doctrine\ORM\EntityManagerInterface;
class SettlementService
{
    public function __construct(private EntityManagerInterface $em) {}
    public function recalculate(Settlement $s): void
    {
        $cargo=0;$desc=0;
        foreach($s->getItems() as $it){
            $m=(float)$it->getMonto();
            if($m<0) throw new \InvalidArgumentException("Monto negativo: ".$it->getDescripcion());
            if($it->getTipo()==='CARGO') $cargo+=$m; else $desc+=$m;
        }
        $neto=max(0,$cargo-$desc);
        $iva=round($neto*((float)($_ENV['IVA_RATE']??0.19)),2);
        $s->setTotalCargo((string)$cargo);$s->setTotalDescuento((string)$desc);
        $s->setTotalNeto((string)$neto);$s->setIva((string)$iva);$s->setTotal((string)($neto+$iva));
    }
    public function pagar(Settlement $s): void
    {
        if($s->getEstado()==='ANULADA') throw new \LogicException("Anulada no se puede pagar");
        if($s->getEstado()==='PAGADA') throw new \LogicException("Ya pagada");
        if((float)$s->getTotal()<=0) throw new \LogicException("Total 0");
        $s->setEstado('PAGADA');$this->em->flush();
    }
    public function anular(Settlement $s): void
    {
        $c=$this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM invoice_settlement WHERE settlement_id=:id",['id'=>$s->getId()]);
        if($c>0) throw new \LogicException("Facturada no se puede anular");
        $s->setEstado('ANULADA');$this->em->flush();
    }
}
