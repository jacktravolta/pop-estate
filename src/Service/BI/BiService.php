<?php
namespace App\Service\BI;
use Doctrine\DBAL\Connection;

class BiService
{
    public function __construct(private Connection $connection) {}

    public function getKpis(?string $companyFilter = null, ?string $comunaFilter = null): array
    {
        $where = $this->buildWhere($companyFilter, $comunaFilter);
        $current = $this->connection->fetchAllAssociative("
            SELECT
            COUNT(DISTINCT c.id) FILTER (WHERE c.deleted_at IS NULL) as empresas_activas,
            COUNT(DISTINCT p.id) FILTER (WHERE p.deleted_at IS NULL) as propiedades_activas,
            COUNT(DISTINCT o.id) as propietarios,
            COUNT(s.id) FILTER (WHERE s.estado = 'PENDIENTE') as liquidaciones_pendientes,
            COUNT(s.id) FILTER (WHERE s.estado = 'PAGADA') as liquidaciones_pagadas,
            COUNT(s.id) FILTER (WHERE s.estado = 'ANULADA') as liquidaciones_anuladas,
            COALESCE(SUM(s.total::numeric) FILTER (WHERE s.estado = 'PAGADA'), 0) as total_facturado,
            COALESCE(SUM(s.total::numeric) FILTER (WHERE s.estado = 'PENDIENTE'), 0) as total_pendiente,
            COALESCE(AVG(s.total::numeric) FILTER (WHERE s.estado = 'PAGADA'), 0) as ticket_promedio
            FROM property p
            LEFT JOIN company c ON p.company_id = c.id
            LEFT JOIN owner o ON p.owner_id = o.id
            LEFT JOIN settlement s ON s.property_id = p.id
            $where
        ")[0];

        $prevMonth = $this->connection->fetchAllAssociative("
            SELECT COALESCE(SUM(s.total::numeric), 0) as total_facturado_prev, COUNT(s.id) as liquidaciones_pagadas_prev
            FROM settlement s JOIN property p ON s.property_id = p.id
            WHERE s.estado = 'PAGADA'
            AND s.fecha_inicio >= (DATE_TRUNC('month', NOW()) - INTERVAL '1 month')
            AND s.fecha_inicio < DATE_TRUNC('month', NOW())
        ")[0];

        $current_total = (float) $current['total_facturado'];
        $prev_total = (float) $prevMonth['total_facturado_prev'];
        $tendencia = $prev_total > 0 ? round((($current_total - $prev_total) / $prev_total) * 100, 1) : 0;

        return [
            'empresas_activas' => (int) $current['empresas_activas'],
            'propiedades_activas' => (int) $current['propiedades_activas'],
            'propietarios' => (int) $current['propietarios'],
            'liquidaciones_pendientes' => (int) $current['liquidaciones_pendientes'],
            'liquidaciones_pagadas' => (int) $current['liquidaciones_pagadas'],
            'liquidaciones_anuladas' => (int) $current['liquidaciones_anuladas'],
            'total_facturado' => $current_total,
            'total_pendiente' => (float) $current['total_pendiente'],
            'ticket_promedio' => (float) $current['ticket_promedio'],
            'tendencia_facturacion' => $tendencia,
        ];
    }

    public function getFacturacionMensual(int $meses = 12, ?string $companyFilter = null): array
    {
        $companyWhere = $companyFilter ? "AND c.id = :companyId" : "";
        $params = ['meses' => (int)$meses];
        if ($companyFilter) $params['companyId'] = (int)$companyFilter;
        
        return $this->connection->fetchAllAssociative(
            "SELECT TO_CHAR(s.fecha_inicio, 'YYYY-MM') as periodo,
            COALESCE(SUM(s.total_neto::numeric), 0) as neto,
            COALESCE(SUM(s.iva::numeric), 0) as iva,
            COALESCE(SUM(s.total::numeric), 0) as total,
            COUNT(s.id) as cantidad
            FROM settlement s JOIN property p ON s.property_id = p.id JOIN company c ON p.company_id = c.id
            WHERE s.estado IN ('PAGADA', 'PENDIENTE')
            AND s.fecha_inicio >= (DATE_TRUNC('month', NOW()) - (:meses || ' months')::interval)
            $companyWhere
            GROUP BY periodo ORDER BY periodo",
            $params
        );
    }

    public function getDistribucionPorComuna(?string $companyFilter = null): array
    {
        $companyWhere = $companyFilter ? "AND c.id = :companyId" : "";
        $params = [];
        if ($companyFilter) $params['companyId'] = (int)$companyFilter;
        
        return $this->connection->fetchAllAssociative(
            "SELECT COALESCE(p.comuna, 'Sin comuna') as comuna, COUNT(p.id) as propiedades,
            COALESCE(SUM(s.total::numeric) FILTER (WHERE s.estado = 'PAGADA'), 0) as facturado
            FROM property p JOIN company c ON p.company_id = c.id
            LEFT JOIN settlement s ON s.property_id = p.id AND s.estado = 'PAGADA'
            WHERE p.deleted_at IS NULL $companyWhere
            GROUP BY p.comuna ORDER BY facturado DESC LIMIT 10",
            $params
        );
    }

    public function getTopPropiedades(?int $limit = 10): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT p.direccion, p.comuna, c.razon_social as empresa, o.nombre as propietario,
            COUNT(s.id) as liquidaciones,
            COALESCE(SUM(s.total::numeric) FILTER (WHERE s.estado = 'PAGADA'), 0) as facturado
            FROM property p JOIN company c ON p.company_id = c.id JOIN owner o ON p.owner_id = o.id
            LEFT JOIN settlement s ON s.property_id = p.id AND s.estado = 'PAGADA'
            WHERE p.deleted_at IS NULL
            GROUP BY p.id, p.direccion, p.comuna, c.razon_social, o.nombre
            ORDER BY facturado DESC LIMIT :limit",
            ['limit' => (int)$limit]
        );
    }

    public function getEstadosLiquidaciones(?string $companyFilter = null): array
    {
        $companyWhere = $companyFilter ? "AND c.id = :companyId" : "";
        $params = [];
        if ($companyFilter) $params['companyId'] = (int)$companyFilter;
        
        return $this->connection->fetchAllAssociative(
            "SELECT s.estado, COUNT(*) as cantidad, COALESCE(SUM(s.total::numeric), 0) as monto
            FROM settlement s JOIN property p ON s.property_id = p.id JOIN company c ON p.company_id = c.id
            WHERE 1=1 $companyWhere GROUP BY s.estado",
            $params
        );
    }

    public function getEmpresas(): array {
        return $this->connection->fetchAllAssociative("SELECT id, razon_social FROM company WHERE deleted_at IS NULL ORDER BY razon_social");
    }

    public function getComunas(): array {
        return $this->connection->fetchAllAssociative("SELECT DISTINCT comuna FROM property WHERE deleted_at IS NULL AND comuna IS NOT NULL ORDER BY comuna");
    }

    private function buildWhere(?string $companyFilter, ?string $comunaFilter): string {
        $where = [];
        if ($companyFilter) $where[] = "c.id = " . (int) $companyFilter;
        if ($comunaFilter) $where[] = "p.comuna = '" . addslashes($comunaFilter) . "'";
        return empty($where) ? "" : "WHERE " . implode(" AND ", $where);
    }
}
