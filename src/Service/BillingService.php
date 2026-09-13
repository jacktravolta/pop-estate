<?php
namespace App\Service;
use App\Entity\Invoice; use App\Entity\Settlement;
use Doctrine\ORM\EntityManagerInterface; use Doctrine\DBAL\Connection;
class BillingService
{
    public function __construct(private EntityManagerInterface $em, private Connection $connection, private SettlementService $settlementService){}
    public function preview(string $periodo, ?int $companyId=null): array
    {
        if(!preg_match('/^\d{4}-\d{2}$/',$periodo)) throw new \InvalidArgumentException("Periodo YYYY-MM");
        $qb=$this->em->createQueryBuilder()->select('s','p','o','c')->from(Settlement::class,'s')->join('s.property','p')->join('p.owner','o')->join('p.company','c')->leftJoin('App\Entity\InvoiceSettlement','is','WITH','is.settlement=s')->leftJoin('is.invoice','i')->where('s.estado=:e')->andWhere('s.fechaInicio LIKE :pe OR s.fechaTermino LIKE :pe')->andWhere('i.id IS NULL')->setParameter('e','PAGADA')->setParameter('pe',$periodo.'%');
        if($companyId) $qb->andWhere('c.id=:cid')->setParameter('cid',$companyId);
        $settlements=$qb->getQuery()->getResult(); $neto=0;$items=[];
        foreach($settlements as $s){$neto+=(float)$s->getTotalNeto(); foreach($s->getItems() as $it){$items[]=['settlement_id'=>$s->getId(),'direccion'=>$s->getProperty()->getDireccion(),'descripcion'=>$it->getDescripcion(),'tipo'=>$it->getTipo(),'monto'=>(float)$it->getMonto()];}}
        $iva=round($neto*((float)($_ENV['IVA_RATE']??0.19))); return ['periodo'=>$periodo,'liquidaciones_count'=>count($settlements),'liquidaciones'=>$settlements,'items'=>$items,'neto'=>$neto,'iva'=>$iva,'total'=>$neto+$iva,'ya_facturado'=>$this->isPeriodoFacturado($periodo)];
    }
    public function isPeriodoFacturado(string $p): bool { return (int)$this->connection->fetchOne("SELECT COUNT(*) FROM invoice WHERE periodo=:p AND estado='EMITIDA'",['p'=>$p])>0; }
    public function createInvoice(string $periodo,string $emisor,string $rutEmisor,?int $companyId=null): array
    {
        $prev=$this->preview($periodo,$companyId);
        if($prev['ya_facturado']) throw new \LogicException("Periodo $periodo ya facturado");
        if($prev['liquidaciones_count']==0) throw new \LogicException("No hay liquidaciones PAGADAS para $periodo");
        $inv=new Invoice(); $inv->setFolio($this->nextFolio()); $inv->setEmisor($emisor); $inv->setRutEmisor($rutEmisor); $inv->setFecha(new \DateTime()); $inv->setPeriodo($periodo); $inv->setNeto((string)$prev['neto']); $inv->setIva((string)$prev['iva']); $inv->setTotal((string)$prev['total']); $inv->setEstado('EMITIDA');
        foreach($prev['liquidaciones'] as $s){$is=new \App\Entity\InvoiceSettlement(); $is->setInvoice($inv); $is->setSettlement($s); $this->em->persist($is);}
        $this->em->persist($inv); $this->em->flush();
        return ['invoice'=>$inv,'archivo_plano'=>$this->buildArchivoPlano($inv,$prev),'preview'=>$prev];
    }
    public function buildArchivoPlano(Invoice $inv,array $prev): string
    {
        if(empty($prev['liquidaciones'])) return '';
        $owner=$prev['liquidaciones'][0]->getProperty()->getOwner();
        $san=function($t){return str_replace([';',"\n","\r"],[' ',' ',''],trim($t));};
        $lines=[]; $lines[]=sprintf("->Encabezado<- 33;%d;%s;0;0;%s;%s;%s;%s;%s;%s;%s;",$inv->getFolio(),$inv->getFecha()->format('Y-m-d'),$owner->getRut(),$san($owner->getNombre()),$san($owner->getGiro()??'Arriendo'),$san($owner->getDireccion()??''),$san($owner->getComuna()??''),$san($owner->getCiudad()??''),$owner->getEmail()??'');
        $lines[]=sprintf("->Totales<- 0;0;0;0;%d;0;%d;%d;%d;",(int)$prev['neto'],(int)((float)($_ENV['IVA_RATE']??0.19)*100),(int)$prev['iva'],(int)$prev['total']);
        $n=1; foreach($prev['items'] as $it){$v=$it['tipo']==='DESCUENTO'?-$it['monto']:$it['monto']; $d=sprintf('%s - %s [%s]',$it['direccion'],$it['descripcion'],$it['tipo']); $lines[]=sprintf("->Detalle<- %d;%s;%s;1;%d;0;0;0;0;0;%d;INT1;UN;;",$n,'LIQ-'.$it['settlement_id'],$san($d),(int)abs($v),(int)$v); $n++; if($n>60) break;}
        return implode("\n",$lines)."\n";
    }
    private function nextFolio(): int { return (int)$this->connection->fetchOne("SELECT COALESCE(MAX(folio),0) FROM invoice")+1; }
}
