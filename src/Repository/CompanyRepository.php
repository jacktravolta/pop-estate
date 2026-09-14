<?php
namespace App\Repository;
use App\Entity\Company;
use App\Validator\RutHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry){ parent::__construct($registry, Company::class); }

    public function createFilteredQueryBuilder(?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')->where('c.deletedAt IS NULL')->orderBy('c.id','DESC');
        if ($q) {
            $qb->andWhere('c.rut LIKE :q OR c.razonSocial LIKE :q OR c.giro LIKE :q OR c.email LIKE :q OR c.comuna LIKE :q')->setParameter('q','%'.$q.'%');
        }
        return $qb;
    }

    public function getKpiData(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $total = (int)$conn->fetchOne('SELECT COUNT(*) FROM company WHERE deleted_at IS NULL');
        $conGiro = (int)$conn->fetchOne("SELECT COUNT(*) FROM company WHERE deleted_at IS NULL AND giro IS NOT NULL AND giro!=''");
        $conEmail = (int)$conn->fetchOne("SELECT COUNT(*) FROM company WHERE deleted_at IS NULL AND email IS NOT NULL AND email!=''");
        $esteMes = (int)$conn->fetchOne("SELECT COUNT(*) FROM company WHERE deleted_at IS NULL AND created_at >= date_trunc('month', NOW())");
        $conProps = (int)$conn->fetchOne('SELECT COUNT(DISTINCT company_id) FROM property WHERE company_id IS NOT NULL AND deleted_at IS NULL');
        $totalProps = (int)$conn->fetchOne('SELECT COUNT(*) FROM property WHERE deleted_at IS NULL AND company_id IS NOT NULL');
        return ['total'=>$total,'conGiro'=>$conGiro,'conEmail'=>$conEmail,'conDireccionOk'=>0,'esteMes'=>$esteMes,'conProps'=>$conProps,'totalProps'=>$totalProps];
    }

    public function hasActiveProperties(Company $company): bool
    {
        $count = $this->getEntityManager()->createQuery('SELECT COUNT(p.id) FROM App\Entity\Property p WHERE p.company = :company AND p.deletedAt IS NULL')->setParameter('company',$company)->getSingleScalarResult();
        return $count>0;
    }
    public function countPropertiesByCompany(Company $company): int
    {
        return (int)$this->getEntityManager()->createQuery('SELECT COUNT(p.id) FROM App\Entity\Property p WHERE p.company = :company AND p.deletedAt IS NULL')->setParameter('company',$company)->getSingleScalarResult();
    }
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')->where('c.deletedAt IS NULL')->orderBy('c.razonSocial','ASC')->getQuery()->getResult();
    }

    /**
     * FIX: busca por RUT limpio, evita duplicado 14137654-3 vs 14.137.654-3
     */
    public function findByRut(string $rut): ?Company
    {
        $cleanInput = RutHelper::clean($rut);
        // Trae candidatos con mismo cuerpo limpio (postgres no tiene REPLACE fácil, filtramos en PHP)
        $all = $this->createQueryBuilder('c')->where('c.deletedAt IS NULL')->getQuery()->getResult();
        foreach ($all as $c) {
            if (RutHelper::clean($c->getRut()) === $cleanInput) return $c;
        }
        return null;
    }
}
