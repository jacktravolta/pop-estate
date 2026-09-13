<?php
namespace App\DataFixtures;
use App\Entity\Company; use App\Entity\Owner; use App\Entity\Property; use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture; use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture {
    public function __construct(private UserPasswordHasherInterface $hasher) {}
    public function load(ObjectManager $m): void {
        $users = [];
        foreach([['admin@pop.cl','admin123',['ROLE_ADMIN']],['contador@pop.cl','admin123',['ROLE_ACCOUNTANT']],['usuario@pop.cl','admin123',['ROLE_USER']],['visor@pop.cl','admin123',['ROLE_VIEWER']]] as $d) {
            $u = new User(); $u->setEmail($d[0]); $u->setRoles($d[2]); $u->setPassword($this->hasher->hashPassword($u, $d[1]));
            $m->persist($u); $users[$d[0]] = $u;
        }
        $cos = [];
        foreach([['76.123.456-7','Inmobiliaria Demo SpA','Administración','Av. Providencia 1208','Providencia','Santiago','contacto@demo.cl'],['99.888.777-6','Constructora Andes Ltda.','Construcción','Apoquindo 4500','Las Condes','Santiago','ventas@andes.cl']] as $d) {
            $c = new Company(); $c->setRut($d[0]); $c->setRazonSocial($d[1]); $c->setGiro($d[2]); $c->setDireccion($d[3]); $c->setComuna($d[4]); $c->setCiudad($d[5]); $c->setEmail($d[6]); $c->setCreatedBy($users['admin@pop.cl']);
            $m->persist($c); $cos[$d[0]] = $c;
        }
        $ows = [];
        foreach([['12.345.678-9','Juan Pérez','Particular','Irarrázaval 2500','Ñuñoa','Santiago','juan@email.cl'],['15.678.901-2','María González','Particular','Manuel Montt 890','Providencia','Santiago','maria@email.cl']] as $d) {
            $o = new Owner(); $o->setRut($d[0]); $o->setNombre($d[1]); $o->setGiro($d[2]); $o->setDireccion($d[3]); $o->setComuna($d[4]); $o->setCiudad($d[5]); $o->setEmail($d[6]);
            $m->persist($o); $ows[$d[0]] = $o;
        }
        foreach([['Av. Providencia 2305, Depto 402','123-45','Providencia','76.123.456-7','12.345.678-9'],['Av. Apoquindo 6000, Depto 1501','234-56','Las Condes','76.123.456-7','15.678.901-2']] as $d) {
            $p = new Property(); $p->setDireccion($d[0]); $p->setRol($d[1]); $p->setComuna($d[2]); $p->setCiudad('Santiago'); $p->setCompany($cos[$d[3]]); $p->setOwner($ows[$d[4]]);
            $m->persist($p);
        }
        $m->flush();
    }
}
