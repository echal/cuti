<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:set-default-password',
    description: 'Set default password (NIP) for all users',
)]
class SetDefaultPasswordCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $users = $this->userRepository->findAll();
        $updated = 0;
        
        foreach ($users as $user) {
            if ($user->getNip()) {
                // Set password = NIP
                $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getNip());
                $user->setPassword($hashedPassword);
                
                $this->entityManager->persist($user);
                $updated++;
                
                $io->text(sprintf('Updated password for: %s (NIP: %s)', 
                    $user->getNama(), 
                    $user->getNip()
                ));
            }
        }
        
        $this->entityManager->flush();
        
        $io->success(sprintf('Successfully updated passwords for %d users', $updated));
        
        return Command::SUCCESS;
    }
}