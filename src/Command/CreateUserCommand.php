<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\UnitKerja;
use App\Repository\UnitKerjaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user for testing security configuration',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UnitKerjaRepository $unitKerjaRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nip', InputArgument::REQUIRED, 'NIP (18 digits)')
            ->addArgument('nama', InputArgument::REQUIRED, 'Full name')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address')
            ->addArgument('role', InputArgument::REQUIRED, 'Role (USER, APPROVER, ADMIN)')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password (default: NIP)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $nip = $input->getArgument('nip');
        $nama = $input->getArgument('nama');
        $email = $input->getArgument('email');
        $role = strtoupper($input->getArgument('role'));
        $password = $input->getArgument('password') ?: $nip;
        
        // Validate NIP
        if (!preg_match('/^\d{18}$/', $nip)) {
            $io->error('NIP must be exactly 18 digits');
            return Command::FAILURE;
        }
        
        // Validate role
        $validRoles = ['USER', 'APPROVER', 'ADMIN'];
        if (!in_array($role, $validRoles)) {
            $io->error('Role must be one of: ' . implode(', ', $validRoles));
            return Command::FAILURE;
        }
        
        // Get or create default unit kerja
        $unitKerja = $this->unitKerjaRepository->findOneBy(['kode' => '001']);
        if (!$unitKerja) {
            $unitKerja = new UnitKerja();
            $unitKerja->setKode('001')->setNama('Unit Default');
            $this->entityManager->persist($unitKerja);
        }
        
        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['nip' => $nip]);
        if ($existingUser) {
            $io->error('User with NIP ' . $nip . ' already exists');
            return Command::FAILURE;
        }
        
        // Create user
        $user = new User();
        $user->setNip($nip)
            ->setNama($nama)
            ->setEmail($email)
            ->setJenisKelamin('L')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Staff')
            ->setUnitKerja($unitKerja)
            ->setJumlahAnak(0)
            ->setStatusPegawai('aktif')
            ->setRoles(['ROLE_' . $role]);
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->success(sprintf(
            'User created successfully!%sNIP: %s%sName: %s%sEmail: %s%sRole: ROLE_%s',
            PHP_EOL,
            $nip,
            PHP_EOL,
            $nama,
            PHP_EOL,
            $email,
            PHP_EOL,
            $role
        ));
        
        return Command::SUCCESS;
    }
}