<?php
namespace App\Command;

use App\Entity\Company;
use App\Entity\Owner;
use App\Entity\Property;
use App\Entity\Settlement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed:settlements', description: 'Carga datos base + 148 PAGADA dic 2025 (usa tu AppFixtures existente)')]
class SeedSettlementsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Cantidad PAGADA', 148)
             ->addOption('truncate', null, InputOption::VALUE_NONE, 'Borra settlements y propiedades previas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int)$input->getOption('count');

        if ($input->getOption('truncate')) {
            $this->em->createQuery('DELETE FROM App\Entity\Settlement s')->execute();
            $this->em->createQuery('DELETE FROM App\Entity\Property p')->execute();
        }

        // === 1. Reusa tu AppFixtures ===
        $users = [];
        foreach ([['admin@pop.cl','admin123',['ROLE_ADMIN']],['user@pop.cl','user123',['ROLE_USER']]] as $d) {
            $existing = $this->em->getRepository(User::class)->findOneBy(['email'=>$d[0]]);
            if ($existing) { $users[$d[0]] = $existing; continue; }
            $u = new User(); $u->setEmail($d[0]); $u->setRoles($d[2]); $u->setPassword($this->hasher->hashPassword($u, $d[1]));
            $this->em->persist($u); $users[$d[0]] = $u;
        }
        $this->em->flush();

        $cos = [];
        foreach ([['76.123.456-7','Inmobiliaria Demo SpA','Administración','Av. Providencia 1208','Providencia','Santiago','contacto@demo.cl'],['99.888.777-6','Constructora Andes Ltda.','Construcción','Apoquindo 4500','Las Condes','Santiago','ventas@andes.cl']] as $d) {
            $c = $this->em->getRepository(Company::class)->findOneBy(['rut'=>$d[0]])?? new Company();
            $c->setRut($d[0]); $c->setRazonSocial($d[1]); $c->setGiro($d[2]); $c->setDireccion($d[3]); $c->setComuna($d[4]); $c->setCiudad($d[5]); $c->setEmail($d[6]); $c->setCreatedBy($users['admin@pop.cl']);
            $this->em->persist($c); $cos[$d[0]] = $c;
        }

        $ows = [];
        foreach ([['12.345.678-9','Juan Pérez','Particular','Irarrázaval 2500','Ñuñoa','Santiago','juan@email.cl'],['15.678.901-2','María González','Particular','Manuel Montt 890','Providencia','Santiago','maria@email.cl']] as $d) {
            $o = $this->em->getRepository(Owner::class)->findOneBy(['rut'=>$d[0]])?? new Owner();
            $o->setRut($d[0]); $o->setNombre($d[1]); $o->setGiro($d[2]); $o->setDireccion($d[3]); $o->setComuna($d[4]); $o->setCiudad($d[5]); $o->setEmail($d[6]);
            $this->em->persist($o); $ows[$d[0]] = $o;
        }
        $this->em->flush();

        $props = $this->em->getRepository(Property::class)->findAll();
        if (count($props) < 2) {
            foreach ([['Av. Providencia 2305, Depto 402','123-45','Providencia','76.123.456-7','12.345.678-9'],['Av. Apoquindo 6000, Depto 1501','234-56','Las Condes','76.123.456-7','15.678.901-2']] as $d) {
                $p = new Property(); $p->setDireccion($d[0]); $p->setRol($d[1]); $p->setComuna($d[2]); $p->setCiudad('Santiago'); $p->setCompany($cos[$d[3]]); $p->setOwner($ows[$d[4]]);
                $this->em->persist($p);
            }
            $this->em->flush();
        }

        // Crea hasta 80 propiedades si faltan para distribuir 148
        $props = $this->em->getRepository(Property::class)->findAll();
        if (count($props) < 80) {
            for ($p=3;$p<=80;$p++) {
                $pr = new Property(); $pr->setDireccion("Av Providencia $p - Depto $p"); $pr->setRol("$p-0$p"); $pr->setComuna('Providencia'); $pr->setCiudad('Santiago'); $pr->setCompany($cos['76.123.456-7']); $pr->setOwner($ows['12.345.678-9']);
                $this->em->persist($pr);
            }
            $this->em->flush();
            $props = $this->em->getRepository(Property::class)->findAll();
        }

        // === 2. 148 PAGADA dic 2025 ===
        $io->info("Sembrando $count PAGADA dic 2025...");
        for ($i=1;$i<=$count;$i++) {
            $s = new Settlement();
            $s->setProperty($props[array_rand($props)]);
            $s->setFechaInicio(new \DateTime('2025-12-01'));
            $s->setFechaTermino(new \DateTime('2025-12-31'));
            $s->setTotal(rand(300000, 1200000));
            $s->setEstado('PAGADA');
            $s->setObservacion("Seed PAGADA #$i dic 2025");
            if (method_exists($s, 'setCreatedAt')) $s->setCreatedAt(new \DateTimeImmutable());
            if (method_exists($s, 'setCreatedBy')) $s->setCreatedBy($users['admin@pop.cl']);
            $this->em->persist($s);
            if ($i % 50 === 0) $this->em->flush();
        }

        $s = new Settlement(); $s->setProperty($props[0]); $s->setFechaInicio(new \DateTime('2025-12-01')); $s->setFechaTermino(new \DateTime('2025-12-31')); $s->setTotal(673910); $s->setEstado('ANULADA'); $s->setObservacion("ANULADA test"); if (method_exists($s, 'setCreatedAt')) $s->setCreatedAt(new \DateTimeImmutable()); if (method_exists($s, 'setCreatedBy')) $s->setCreatedBy($users['admin@pop.cl']); $this->em->persist($s);
        $this->em->flush();

        $io->success("Listo: ".count($props)." propiedades y $count PAGADA");
        return Command::SUCCESS;
    }
}
