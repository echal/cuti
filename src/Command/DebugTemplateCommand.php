<?php

namespace App\Command;

use App\Repository\PengajuanCutiRepository;
use App\Repository\KonfigurasiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

#[AsCommand(
    name: 'app:debug-template',
    description: 'Debug template variables untuk PDF'
)]
class DebugTemplateCommand extends Command
{
    public function __construct(
        private readonly PengajuanCutiRepository $pengajuanCutiRepository,
        private readonly KonfigurasiRepository $konfigurasiRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID pengajuan cuti');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $id = (int) $input->getArgument('id');
        
        $pengajuan = $this->pengajuanCutiRepository->find($id);
        if (!$pengajuan) {
            $io->error("Pengajuan cuti dengan ID {$id} tidak ditemukan");
            return Command::FAILURE;
        }

        // Get kepala kantor configuration
        $kepalaKantor = $this->konfigurasiRepository->getKepalaKantor();

        // Get hak cuti data
        $currentYear = (int) $pengajuan->getTanggalMulai()->format('Y');
        $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
        $hakCutiUser = $hakCutiRepo->findByUserAndTahun($pengajuan->getUser(), $currentYear);

        $io->section('Data Pengajuan Cuti');
        $io->table(
            ['Field', 'Value'],
            [
                ['ID', $pengajuan->getId()],
                ['User', $pengajuan->getUser()->getNama()],
                ['NIP', $pengajuan->getUser()->getNip()],
                ['Jabatan', $pengajuan->getUser()->getJabatan() ?: '-'],
                ['Masa Kerja', $pengajuan->getUser()->getMasaKerja() ?: '-'],
                ['Golongan', $pengajuan->getUser()->getGolongan() ?: '-'],
                ['Telepon', $pengajuan->getUser()->getTelp() ?: '-'],
                ['Unit Kerja', $pengajuan->getUser()->getUnitKerja()->getNama()],
                ['Jenis Cuti', $pengajuan->getJenisCuti()->getNama()],
                ['Alasan', substr($pengajuan->getAlasan(), 0, 50) . '...'],
                ['Alamat Cuti', $pengajuan->getAlamatCuti() ?: 'NULL'],
                ['Nomor Surat', $pengajuan->getNomorSurat() ?: 'NULL'],
                ['Status', $pengajuan->getStatus()],
                ['Tanggal Mulai', $pengajuan->getTanggalMulai()->format('Y-m-d')],
                ['Tanggal Selesai', $pengajuan->getTanggalSelesai()->format('Y-m-d')],
                ['Lama Cuti', $pengajuan->getLamaCuti() . ' hari'],
            ]
        );

        $io->section('Data Kepala Kantor');
        $io->table(
            ['Field', 'Value'],
            [
                ['Nama', $kepalaKantor['nama']],
                ['NIP', $kepalaKantor['nip']],
                ['Jabatan', $kepalaKantor['jabatan']],
            ]
        );

        $io->section('Data Hak Cuti');
        if ($hakCutiUser) {
            $io->table(
                ['Field', 'Value'],
                [
                    ['Tahun', $hakCutiUser->getTahun()],
                    ['Hak Tahunan', $hakCutiUser->getHakTahunan()],
                    ['Terpakai', $hakCutiUser->getTerpakai()],
                    ['Sisa', $hakCutiUser->getSisa()],
                ]
            );
        } else {
            $io->warning("Data hak cuti tidak ditemukan untuk tahun {$currentYear}");
        }

        return Command::SUCCESS;
    }
}