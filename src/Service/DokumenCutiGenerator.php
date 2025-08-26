<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PengajuanCuti;
use App\Entity\DokumenCuti;
use App\Repository\DokumenCutiRepository;
use App\Repository\KonfigurasiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class DokumenCutiGenerator
{
    private const UPLOAD_DIR = 'public/uploads/dokumen/';

    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly DokumenCutiRepository $dokumenCutiRepository,
        private readonly KonfigurasiRepository $konfigurasiRepository,
        private readonly string $projectDir
    ) {
    }

    /**
     * Generate PDF document for pengajuan cuti
     */
    public function generate(PengajuanCuti $pengajuan): string
    {
        // Check if document already exists
        $existingDokumen = $this->dokumenCutiRepository->findByPengajuanCuti($pengajuan);
        if ($existingDokumen && $existingDokumen->fileExists()) {
            return $existingDokumen->getPathFile();
        }

        // Generate document number
        $nomorDokumen = $this->generateNomorDokumen($pengajuan);
        
        // Generate QR code (optional)
        $qrCode = $this->generateQRCode($pengajuan);

        // Get kepala kantor configuration
        $kepalaKantor = $this->konfigurasiRepository->getKepalaKantor();

        // Get hak cuti data untuk catatan cuti
        $currentYear = (int) $pengajuan->getTanggalMulai()->format('Y');
        $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
        $hakCutiUser = $hakCutiRepo->findByUserAndTahun($pengajuan->getUser(), $currentYear);

        // Calculate cuti sisa data sesuai format template
        $cutiData = [
            'n2sisa' => '',
            'n2ket' => '',
            'n1sisa' => '', 
            'n1ket' => '',
            'nsisa' => $hakCutiUser ? $hakCutiUser->getSisa() : '',
            'nket' => ''
        ];

        // Get previous years data if available
        $hakCutiN1 = $hakCutiRepo->findByUserAndTahun($pengajuan->getUser(), $currentYear - 1);
        if ($hakCutiN1) {
            $cutiData['n1sisa'] = $hakCutiN1->getSisa();
            $cutiData['n1ket'] = $hakCutiN1->getSisa() > 0 ? 'Sisa cuti tahun sebelumnya' : '';
        }

        $hakCutiN2 = $hakCutiRepo->findByUserAndTahun($pengajuan->getUser(), $currentYear - 2);
        if ($hakCutiN2) {
            $cutiData['n2sisa'] = $hakCutiN2->getSisa();
            $cutiData['n2ket'] = $hakCutiN2->getSisa() > 0 ? 'Sisa cuti 2 tahun sebelumnya' : '';
        }

        // Map jenis cuti ke format yang sesuai
        $jenisCutiNama = strtolower($pengajuan->getJenisCuti()->getNama());
        $jenisCuti = 'tahunan'; // default
        
        if (strpos($jenisCutiNama, 'tahunan') !== false) {
            $jenisCuti = 'tahunan';
        } elseif (strpos($jenisCutiNama, 'besar') !== false) {
            $jenisCuti = 'besar';
        } elseif (strpos($jenisCutiNama, 'sakit') !== false) {
            $jenisCuti = 'sakit';
        } elseif (strpos($jenisCutiNama, 'melahirkan') !== false) {
            $jenisCuti = 'melahirkan';
        } elseif (strpos($jenisCutiNama, 'penting') !== false) {
            $jenisCuti = 'penting';
        } elseif (strpos($jenisCutiNama, 'luar tanggungan') !== false || strpos($jenisCutiNama, 'cltn') !== false) {
            $jenisCuti = 'diluar';
        }

        // Create cuti object sesuai format template HTML  
        $cutiObject = (object) [
            'jenis' => $jenisCuti,
            'alasan' => $pengajuan->getAlasan(),
            'lama' => $pengajuan->getLamaCuti(),
            'tanggalMulai' => $pengajuan->getTanggalMulai(),
            'tanggalSelesai' => $pengajuan->getTanggalSelesai(),
            'alamatCuti' => $pengajuan->getAlamatCuti() ?: 'Alamat sesuai KTP',
            'telp' => $pengajuan->getUser()->getTelp() ?: '-',
            'n2sisa' => $cutiData['n2sisa'],
            'n2ket' => $cutiData['n2ket'],
            'n1sisa' => $cutiData['n1sisa'],
            'n1ket' => $cutiData['n1ket'],
            'nsisa' => $cutiData['nsisa'],
            'nket' => $cutiData['nket']
        ];

        // Create kepala bidang object
        $kepalaBidang = (object) [
            'nama' => $pengajuan->getPejabatAtasan()?->getNama() ?? $kepalaKantor['nama'],
            'nip' => $pengajuan->getPejabatAtasan()?->getNip() ?? $kepalaKantor['nip']
        ];

        // Create kepala object (sama dengan kepala_kantor)
        $kepala = (object) [
            'nama' => $kepalaKantor['nama'],
            'nip' => $kepalaKantor['nip']
        ];

        // Date variables untuk template
        $tanggal = $pengajuan->getTanggalPengajuan()->format('d');
        $bulan = $pengajuan->getTanggalPengajuan()->format('m');
        $tahun = $pengajuan->getTanggalPengajuan()->format('Y');
        $nomorSurat = $pengajuan->getNomorSurat() ?: ('001/Kw.31/1/KP.08.2/' . $bulan . '/' . $tahun);

        // Create user object untuk template
        $userObject = (object) [
            'nama' => $pengajuan->getUser()->getNama(),
            'nip' => $pengajuan->getUser()->getNip() ?: '-',
            'jabatan' => $pengajuan->getUser()->getJabatan() ?: '-',
            'masaKerja' => $pengajuan->getUser()->getMasaKerja() ?: '-',
            'unitKerja' => $pengajuan->getUser()->getUnitKerja() ? $pengajuan->getUser()->getUnitKerja()->getNama() : '-',
            'golongan' => $pengajuan->getUser()->getGolongan() ?: '-'
        ];

        // Convert bulan ke nama Indonesia
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulanIndonesia = $monthNames[(int)$bulan];

        // Render HTML template - gunakan template ASN untuk status PNS atau template default untuk PPPK
        $templateName = $pengajuan->getUser()->getStatusKepegawaian() === 'PNS' ? 'cuti/print_asn.html.twig' : 'dokumen/cuti.html.twig';
        
        $html = $this->twig->render($templateName, array_merge([
            'pengajuan' => $pengajuan,
            'user' => $userObject,
            'cuti' => $cutiObject,
            'kepala_kantor' => $kepalaKantor,
            'kepala' => $kepala,
            'kepalaBidang' => $kepalaBidang,
            'dokumen' => $existingDokumen,
            'qrCode' => $qrCode,
            'nomorDokumen' => $nomorDokumen,
            'nomor_surat' => $nomorSurat,
            'tanggal' => $tanggal,
            'bulan' => $bulanIndonesia,
            'tahun' => $tahun,
        ], $cutiData));

        // Generate PDF
        $pdfContent = $this->generatePDF($html);
        
        // Save to file
        $filePath = $this->savePDFToFile($pdfContent, $pengajuan);
        
        // Create or update DokumenCuti entity
        $dokumen = $existingDokumen ?: new DokumenCuti();
        $dokumen->setPengajuanCuti($pengajuan);
        $dokumen->setNamaFile($this->generateFileName($pengajuan));
        $dokumen->setPathFile($filePath);
        $dokumen->setNomorDokumen($nomorDokumen);

        $this->entityManager->persist($dokumen);
        $this->entityManager->flush();

        return $filePath;
    }

    /**
     * Generate PDF content from HTML
     */
    private function generatePDF(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Times');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Change to Legal size paper (21.59cm x 33cm)
        $dompdf->setPaper([0, 0, 612, 936], 'portrait'); // Legal size in points
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Save PDF content to file
     */
    private function savePDFToFile(string $pdfContent, PengajuanCuti $pengajuan): string
    {
        $uploadDir = $this->projectDir . '/' . self::UPLOAD_DIR;
        
        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $this->generateFileName($pengajuan);
        $filePath = $uploadDir . $fileName;

        file_put_contents($filePath, $pdfContent);

        return $filePath;
    }

    /**
     * Generate unique filename for PDF
     */
    private function generateFileName(PengajuanCuti $pengajuan): string
    {
        $timestamp = $pengajuan->getTanggalPengajuan()->format('Ymd_His');
        $userId = $pengajuan->getUser()->getId();
        $pengajuanId = $pengajuan->getId();
        
        return "cuti_{$userId}_{$pengajuanId}_{$timestamp}.pdf";
    }

    /**
     * Generate document number
     */
    private function generateNomorDokumen(PengajuanCuti $pengajuan): string
    {
        $tahun = $pengajuan->getTanggalMulai()->format('Y');
        $bulan = $pengajuan->getTanggalMulai()->format('m');
        $jenisCutiKode = $pengajuan->getJenisCuti()->getKode();
        $pengajuanId = str_pad((string) $pengajuan->getId(), 3, '0', STR_PAD_LEFT);
        
        // Format: 001/CT/03/2024
        return "{$pengajuanId}/{$jenisCutiKode}/{$bulan}/{$tahun}";
    }

    /**
     * Generate QR code for verification (optional)
     */
    private function generateQRCode(PengajuanCuti $pengajuan): ?string
    {
        // Simple verification code - in production, you might use a proper QR code library
        $verificationCode = base64_encode(
            $pengajuan->getId() . '-' . 
            $pengajuan->getUser()->getId() . '-' . 
            $pengajuan->getTanggalMulai()->format('Ymd')
        );
        
        // For now, return the verification code as text
        // You can implement proper QR code generation using libraries like endroid/qr-code
        return $verificationCode;
    }

    /**
     * Get file path for existing document
     */
    public function getDocumentPath(PengajuanCuti $pengajuan): ?string
    {
        $dokumen = $this->dokumenCutiRepository->findByPengajuanCuti($pengajuan);
        
        if (!$dokumen || !$dokumen->fileExists()) {
            return null;
        }
        
        return $dokumen->getPathFile();
    }

    /**
     * Check if document exists for pengajuan
     */
    public function hasDocument(PengajuanCuti $pengajuan): bool
    {
        return $this->getDocumentPath($pengajuan) !== null;
    }

    /**
     * Regenerate PDF document (force recreate)
     */
    public function regenerate(PengajuanCuti $pengajuan): string
    {
        // Delete existing document first
        $this->deleteDocument($pengajuan);
        
        // Generate new document
        return $this->generate($pengajuan);
    }

    /**
     * Delete document file and entity
     */
    public function deleteDocument(PengajuanCuti $pengajuan): bool
    {
        $dokumen = $this->dokumenCutiRepository->findByPengajuanCuti($pengajuan);
        
        if (!$dokumen) {
            return false;
        }

        // Delete file
        if ($dokumen->fileExists()) {
            unlink($dokumen->getPathFile());
        }

        // Delete entity
        $this->entityManager->remove($dokumen);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Regenerate document (delete old and create new)
     */
    public function regenerateDocument(PengajuanCuti $pengajuan): string
    {
        $this->deleteDocument($pengajuan);
        return $this->generate($pengajuan);
    }

    /**
     * Get document URL for download
     */
    public function getDocumentUrl(PengajuanCuti $pengajuan): ?string
    {
        $dokumen = $this->dokumenCutiRepository->findByPengajuanCuti($pengajuan);
        
        if (!$dokumen || !$dokumen->fileExists()) {
            return null;
        }

        return '/uploads/dokumen/' . $dokumen->getFileName();
    }

    /**
     * Get document info
     */
    public function getDocumentInfo(PengajuanCuti $pengajuan): ?array
    {
        $dokumen = $this->dokumenCutiRepository->findByPengajuanCuti($pengajuan);
        
        if (!$dokumen) {
            return null;
        }

        return [
            'id' => $dokumen->getId(),
            'nama_file' => $dokumen->getNamaFile(),
            'nomor_dokumen' => $dokumen->getNomorDokumen(),
            'ukuran_file' => $dokumen->getFormattedFileSize(),
            'tanggal_dibuat' => $dokumen->getCreatedAt(),
            'file_exists' => $dokumen->fileExists(),
            'download_url' => $this->getDocumentUrl($pengajuan),
        ];
    }
}