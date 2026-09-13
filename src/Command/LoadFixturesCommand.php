<?php
namespace App\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:fixtures:load', description: 'Carga 100+ datos de prueba')]
class LoadFixturesCommand extends Command
{
    public function __construct(private Connection $connection) { parent::__construct(); }
    protected function configure(): void { $this->addOption('force','f',InputOption::VALUE_NONE,'Forzar'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = (int)$this->connection->fetchOne("SELECT id FROM app_user ORDER BY id ASC LIMIT 1");
        if ($userId === 0) { $io->error('No hay usuario en app_user. Ejecuta app:create-user'); return Command::FAILURE; }

        $nombres = ['Juan','Maria','Carlos','Ana','Pedro','Laura'];
        $apellidos = ['Perez','Gonzalez','Rodriguez','Martinez'];

        for ($i=0;$i<100;$i++) {
            $rut = rand(10,99).'.'.rand(100,999).'.'.rand(100,999).'-'.rand(0,9);
            $nom = $nombres[array_rand($nombres)].' '.$apellidos[array_rand($apellidos)];
            $this->connection->executeStatement("INSERT INTO company (rut, razon_social, giro, direccion, comuna, ciudad, email, created_at, created_by_id) VALUES ('$rut', '$nom SpA', 'Inmobiliaria', 'Av Providencia ".rand(100,9999)."', 'Providencia', 'Santiago', 'emp".uniqid()."@demo.cl', NOW(), $userId)");
        }
        $companyIds = $this->connection->fetchFirstColumn("SELECT id FROM company");

        for ($i=0;$i<100;$i++) {
            $rut = rand(10,99).'.'.rand(100,999).'-'.rand(0,9);
            $nom = $nombres[array_rand($nombres)].' '.$apellidos[array_rand($apellidos)];
            $this->connection->executeStatement("INSERT INTO owner (rut, nombre, giro, direccion, comuna, ciudad, email, created_at) VALUES ('$rut', '$nom', 'Propietario', 'Calle ".rand(100,9999)."', 'Providencia', 'Santiago', 'prop".uniqid()."@demo.cl', NOW())");
        }
        $ownerIds = $this->connection->fetchFirstColumn("SELECT id FROM owner");

        for ($i=0;$i<100;$i++) {
            $companyId = $companyIds[array_rand($companyIds)];
            $ownerId = $ownerIds[array_rand($ownerIds)];
            $rol = rand(100,999).'-'.rand(10,99);
            $this->connection->executeStatement("INSERT INTO property (company_id, owner_id, direccion, rol, comuna, ciudad, created_at) VALUES ($companyId, $ownerId, 'Av Providencia ".rand(1000,9999)."', '$rol', 'Providencia', 'Santiago', NOW())");
        }
        $propertyIds = $this->connection->fetchFirstColumn("SELECT id FROM property");

        $periodos = [['2025-10-01','2025-10-31'],['2025-11-01','2025-11-30'],['2025-12-01','2025-12-31']];
        foreach ($periodos as $p) {
            for ($j=0;$j<50;$j++) {
                $propertyId = $propertyIds[array_rand($propertyIds)];
                $cargo = rand(400000,900000);
                $iva = (int)round($cargo*0.19);
                $this->connection->executeStatement("INSERT INTO settlement (property_id, fecha_inicio, fecha_termino, estado, total_cargo, total_descuento, total_neto, iva, total, created_at, created_by_id) VALUES ($propertyId, '{$p[0]}', '{$p[1]}', 'PAGADA', $cargo, 0, $cargo, $iva, ".($cargo+$iva).", NOW(), $userId)");
                $settId = (int)$this->connection->fetchOne("SELECT MAX(id) FROM settlement");
                $this->connection->executeStatement("INSERT INTO settlement_item (settlement_id, tipo, descripcion, monto) VALUES ($settId, 'CARGO', 'Arriendo', $cargo)");
            }
        }

        for ($i=0;$i<30;$i++) {
            $neto = rand(800000,2000000);
            $iva = (int)round($neto*0.19);
            $this->connection->executeStatement("INSERT INTO invoice (folio, emisor, rut_emisor, fecha, periodo, neto, iva, total, estado, created_at) VALUES (".rand(10000,99999).", 'Pop Estate SpA', '76.123.456-7', NOW(), '2025-12', $neto, $iva, ".($neto+$iva).", 'EMITIDA', NOW())");
        }

        $io->success('TODO CARGADO OK: 100 empresas, 100 owners, 100 properties, 150 settlements, 30 invoices');
        return Command::SUCCESS;
    }
}
