<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-security',
    description: 'Test security configuration with created users',
)]
class TestSecurityCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Security Configuration Test');
        
        // Test user authentication
        $testUsers = [
            ['nip' => '123456789012345678', 'role' => 'ADMIN', 'password' => '123456789012345678'],
            ['nip' => '987654321098765432', 'role' => 'APPROVER', 'password' => '987654321098765432'],
            ['nip' => '111222333444555666', 'role' => 'USER', 'password' => '111222333444555666'],
        ];
        
        $io->section('Testing User Authentication');
        
        foreach ($testUsers as $testUser) {
            $user = $this->userRepository->findOneBy(['nip' => $testUser['nip']]);
            
            if (!$user) {
                $io->error('User not found: ' . $testUser['nip']);
                continue;
            }
            
            // Test password hashing
            $isPasswordValid = $this->passwordHasher->isPasswordValid($user, $testUser['password']);
            
            $io->text(sprintf(
                'User: %s (NIP: %s) - Role: %s - Password Valid: %s',
                $user->getNama(),
                $user->getNip(),
                implode(', ', $user->getRoles()),
                $isPasswordValid ? '✅' : '❌'
            ));
        }
        
        $io->section('Role Hierarchy Test');
        
        foreach ($testUsers as $testUser) {
            $user = $this->userRepository->findOneBy(['nip' => $testUser['nip']]);
            if (!$user) continue;
            
            $roles = $user->getRoles();
            $io->text(sprintf(
                'User: %s - Primary Role: ROLE_%s - All Roles: %s',
                $user->getNama(),
                $testUser['role'],
                implode(', ', $roles)
            ));
        }
        
        $io->section('Route Access Control Summary');
        
        $routes = [
            '/cuti/ (index)' => 'ROLE_USER+',
            '/cuti/new' => 'ROLE_USER+',
            '/cuti/{id}/edit' => 'ROLE_USER (owner only)',
            '/cuti/{id}/cancel' => 'ROLE_USER (owner only)',
            '/cuti/{id}/approve' => 'ROLE_APPROVER+',
            '/cuti/{id}/reject' => 'ROLE_APPROVER+',
            '/admin/users/*' => 'ROLE_ADMIN',
            '/admin/jenis-cuti/*' => 'ROLE_ADMIN',
        ];
        
        foreach ($routes as $route => $access) {
            $io->text(sprintf('%-25s -> %s', $route, $access));
        }
        
        $io->success('Security configuration test completed!');
        
        $io->note([
            'Test Login Credentials:',
            'Admin: NIP=123456789012345678, Password=123456789012345678',
            'Approver: NIP=987654321098765432, Password=987654321098765432',
            'User: NIP=111222333444555666, Password=111222333444555666',
        ]);
        
        return Command::SUCCESS;
    }
}