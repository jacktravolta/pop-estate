<?php
namespace App\Command;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Crea usuario demo')]
class CreateUserCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher){parent::__construct();}
    protected function configure(): void {
        $this->addArgument('email', InputArgument::REQUIRED)->addArgument('password', InputArgument::REQUIRED)->addArgument('roles', InputArgument::IS_ARRAY);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email=$input->getArgument('email'); $pass=$input->getArgument('password'); $roles=$input->getArgument('roles');
        if(empty($roles)) $roles=['ROLE_USER'];
        $repo=$this->em->getRepository(User::class);
        $existing=$repo->findOneBy(['email'=>$email]);
        if($existing){$output->writeln("Usuario $email ya existe, actualizando password..."); $user=$existing; } else { $user=new User(); $user->setEmail($email); }
        $user->setRoles($roles); $user->setPassword($this->hasher->hashPassword($user,$pass));
        $this->em->persist($user); $this->em->flush();
        $output->writeln("✅ Usuario $email creado/actualizado con roles: ".implode(',',$roles));
        return Command::SUCCESS;
    }
}