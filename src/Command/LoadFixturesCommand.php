<?php

declare(strict_types=1);

namespace App\Command;

use App\DataFixtures\UnitKerjaFixtures;
use App\DataFixtures\JenisCutiFixtures;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Load application fixtures data',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append fixtures instead of purging database')
            ->setHelp('This command loads sample data fixtures for the cuti application');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $append = $input->getOption('append');

        $io->title('Loading Application Fixtures');

        try {
            if (!$append) {
                $io->section('Purging database');
                $this->purgeDatabase();
                $io->success('Database purged successfully');
            }

            $io->section('Loading fixtures');

            // Load UnitKerja fixtures
            $io->text('Loading Unit Kerja fixtures...');
            $unitKerjaFixtures = new UnitKerjaFixtures();
            $unitKerjaFixtures->load($this->entityManager);
            $io->text('✓ Unit Kerja fixtures loaded');

            // Load JenisCuti fixtures
            $io->text('Loading Jenis Cuti fixtures...');
            $jenisCutiFixtures = new JenisCutiFixtures();
            $jenisCutiFixtures->load($this->entityManager);
            $io->text('✓ Jenis Cuti fixtures loaded');

            // Load App fixtures (Users, Pejabat, HakCuti)
            $io->text('Loading App fixtures (Users, Pejabat, HakCuti)...');
            $appFixtures = new AppFixtures();
            // Manually set references from previous fixtures
            $this->setFixtureReferences($appFixtures, $unitKerjaFixtures, $jenisCutiFixtures);
            $appFixtures->load($this->entityManager);
            $io->text('✓ App fixtures loaded');

            $io->newLine();
            $io->success('All fixtures loaded successfully!');
            
            $io->section('Sample Data Summary');
            $io->listing([
                '9 Unit Kerja (BAGTU, BIMASISLAM, MADRASAH, PAPKIS, PHU, KRISTEN, KATOLIK, HINDU, BUDDHA)',
                '6 Jenis Cuti (CT, CB, CS, CM, CAP, CLTN)',
                '1 Admin User',
                '2 Pejabat (Kakanwil & Kabag TU)',
                '3 Sample Users (PNS & PPPK)',
                'Hak Cuti for current and previous year'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error loading fixtures: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function purgeDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        
        // Disable foreign key checks
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Get all table names
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        
        // Truncate all tables except doctrine_migration_versions
        foreach ($tables as $table) {
            if ($table !== 'doctrine_migration_versions') {
                $connection->executeStatement('TRUNCATE TABLE ' . $table);
            }
        }
        
        // Re-enable foreign key checks
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function setFixtureReferences(AppFixtures $appFixtures, UnitKerjaFixtures $unitKerjaFixtures, JenisCutiFixtures $jenisCutiFixtures): void
    {
        // Get unit kerja entities to create references
        $unitKerjaRepo = $this->entityManager->getRepository(\App\Entity\UnitKerja::class);
        $units = $unitKerjaRepo->findAll();
        
        foreach ($units as $unit) {
            $appFixtures->setReference('unit_kerja_' . $unit->getKode(), $unit);
        }

        // Get jenis cuti entities to create references
        $jenisCutiRepo = $this->entityManager->getRepository(\App\Entity\JenisCuti::class);
        $jenisCs = $jenisCutiRepo->findAll();
        
        foreach ($jenisCs as $jenis) {
            $appFixtures->setReference('jenis_cuti_' . $jenis->getKode(), $jenis);
        }
    }
}