<?php

namespace App\Command;

use App\Repository\PengajuanCutiRepository;
use App\Service\DokumenCutiGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-pdf',
    description: 'Test PDF generation for pengajuan cuti'
)]
class TestPdfGenerationCommand extends Command
{
    public function __construct(
        private readonly PengajuanCutiRepository $pengajuanCutiRepository,
        private readonly DokumenCutiGenerator $dokumenCutiGenerator
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

        $io->info("Testing PDF generation for pengajuan ID: {$id}");
        $io->info("User: {$pengajuan->getUser()->getNama()}");
        $io->info("Status: {$pengajuan->getStatus()}");
        $io->info("Jenis Cuti: {$pengajuan->getJenisCuti()->getNama()}");

        try {
            $filePath = $this->dokumenCutiGenerator->generate($pengajuan);
            $io->success("PDF berhasil digenerate!");
            $io->info("File path: {$filePath}");
            
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $io->info("File size: " . number_format($fileSize) . " bytes");
            }
            
        } catch (\Exception $e) {
            $io->error("Error generating PDF: " . $e->getMessage());
            $io->note("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}