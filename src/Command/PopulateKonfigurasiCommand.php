<?php

namespace App\Command;

use App\Entity\Konfigurasi;
use App\Repository\KonfigurasiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-konfigurasi',
    description: 'Populate default configuration data'
)]
class PopulateKonfigurasiCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly KonfigurasiRepository $konfigurasiRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if kepala kantor config already exists
        $existingConfig = $this->konfigurasiRepository->findByKey('kepala_kantor_nama');
        if ($existingConfig) {
            $io->warning('Konfigurasi kepala kantor sudah ada. Menghentikan proses.');
            return Command::SUCCESS;
        }

        $io->info('Menambahkan data konfigurasi kepala kantor...');

        // Add kepala kantor nama
        $kepalaKantorNama = new Konfigurasi();
        $kepalaKantorNama->setKey('kepala_kantor_nama');
        $kepalaKantorNama->setValue('Dr. H. Abdul Rahman, M.Pd.');
        $kepalaKantorNama->setDescription('Nama Kepala Kantor');
        $this->entityManager->persist($kepalaKantorNama);

        // Add kepala kantor nip
        $kepalaKantorNip = new Konfigurasi();
        $kepalaKantorNip->setKey('kepala_kantor_nip');
        $kepalaKantorNip->setValue('196812151994031003');
        $kepalaKantorNip->setDescription('NIP Kepala Kantor');
        $this->entityManager->persist($kepalaKantorNip);

        // Add kepala kantor jabatan
        $kepalaKantorJabatan = new Konfigurasi();
        $kepalaKantorJabatan->setKey('kepala_kantor_jabatan');
        $kepalaKantorJabatan->setValue('Kepala Kanwil Kemenag Sulbar');
        $kepalaKantorJabatan->setDescription('Jabatan Kepala Kantor');
        $this->entityManager->persist($kepalaKantorJabatan);

        $this->entityManager->flush();

        $io->success('Data konfigurasi kepala kantor berhasil ditambahkan!');
        $io->table(
            ['Key', 'Value', 'Description'],
            [
                ['kepala_kantor_nama', 'Dr. H. Abdul Rahman, M.Pd.', 'Nama Kepala Kantor'],
                ['kepala_kantor_nip', '196812151994031003', 'NIP Kepala Kantor'],
                ['kepala_kantor_jabatan', 'Kepala Kanwil Kemenag Sulbar', 'Jabatan Kepala Kantor'],
            ]
        );

        return Command::SUCCESS;
    }
}