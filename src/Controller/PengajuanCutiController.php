<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PengajuanCuti;
use App\Entity\RiwayatCuti;
use App\Form\PengajuanCutiType;
use App\Repository\PengajuanCutiRepository;
use App\Security\Voter\PengajuanCutiVoter;
use App\Service\CutiCalculator;
use App\Service\CutiPolicy;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\KonfigurasiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Service\DokumenCutiGenerator;

#[Route('/cuti', name: 'pengajuan_cuti_')]
class PengajuanCutiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CutiPolicy $cutiPolicy,
        private readonly CutiCalculator $cutiCalculator,
        private readonly PengajuanCutiRepository $pengajuanCutiRepository,
        private readonly DokumenCutiGenerator $dokumenCutiGenerator,
        private readonly KonfigurasiRepository $konfigurasiRepository,
        #[Autowire(service: 'state_machine.pengajuan_cuti')]
        private readonly WorkflowInterface $pengajuanCutiWorkflow
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $currentYear = (int) date('Y');
        
        // Get user's hak cuti info for summary cards
        $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
        $hakCutiUser = $hakCutiRepo->findByUserAndTahun($user, $currentYear);
        
        if ($this->isGranted('ROLE_APPROVER') || $this->isGranted('ROLE_ADMIN')) {
            // For Admin: get all requests globally
            if ($this->isGranted('ROLE_ADMIN')) {
                // Admin dapat melihat semua pengajuan
                $pengajuanList = $this->pengajuanCutiRepository->findAllPendingApproval();
                $processedList = $this->pengajuanCutiRepository->findAllProcessed(); 
                $myRequests = $this->pengajuanCutiRepository->findByUser($user);
                
                // Debug: Log counts for admin
                error_log("DEBUG ADMIN: Pending = " . count($pengajuanList) . ", Processed = " . count($processedList) . ", MyRequests = " . count($myRequests));
                
                // Combine all for display (admin sees everything)
                $allRequests = array_merge(
                    $this->pengajuanCutiRepository->findAllPendingApproval(),
                    $this->pengajuanCutiRepository->findAllProcessed()
                );
                $pengajuanList = $allRequests;
            } else {
                // Approver: hanya lihat pengajuan di unit kerjanya
                $pengajuanList = $this->pengajuanCutiRepository->findForApprovalByUnit($user->getUnitKerja(), null);
                $processedList = $this->pengajuanCutiRepository->findProcessedByUnit($user->getUnitKerja());
                $myRequests = $this->pengajuanCutiRepository->findByUser($user);
                
                // Debug: Log counts for approver
                error_log("DEBUG APPROVER: Unit = " . $user->getUnitKerja()->getNama() . ", Pending = " . count($pengajuanList) . ", Processed = " . count($processedList) . ", MyRequests = " . count($myRequests));
            }
        } else {
            // User biasa: hanya lihat pengajuan milik sendiri
            $pengajuanList = $this->pengajuanCutiRepository->findByUser($user);
            $processedList = [];
            $myRequests = $pengajuanList; // Same as pengajuan_list for regular users
        }

        // Calculate summary statistics for user
        $totalTerpakai = $this->pengajuanCutiRepository->getTotalHariCutiByUserAndYear($user, $currentYear);
        $dalamProses = 0;
        
        // Calculate dari pengajuan milik user sendiri
        $userRequests = $this->pengajuanCutiRepository->findByUser($user);
        foreach ($userRequests as $pengajuan) {
            if (in_array($pengajuan->getStatus(), ['draft', 'diajukan'])) {
                $dalamProses += $pengajuan->getLamaCuti();
            }
        }

        return $this->render('pengajuan_cuti/index.html.twig', [
            'pengajuan_list' => $pengajuanList,
            'processed_list' => $processedList ?? [],
            'my_requests' => $myRequests ?? [],
            'hak_cuti' => $hakCutiUser,
            'summary' => [
                'hak_tahunan' => $hakCutiUser ? $hakCutiUser->getHakTahunan() : 12,
                'cuti_tahun_lalu' => 0, // TODO: Implement carry over calculation
                'cuti_dua_tahun_lalu' => 0, // TODO: Implement carry over calculation  
                'terpakai' => $hakCutiUser ? $hakCutiUser->getTerpakai() : $totalTerpakai,
                'sisa' => $hakCutiUser ? $hakCutiUser->getSisa() : (12 - $totalTerpakai - $dalamProses),
                'dalam_proses' => $dalamProses
            ]
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        $pengajuan = new PengajuanCuti();
        $pengajuan->setUser($user);
        $pengajuan->setStatus('draft');
        $pengajuan->setTanggalPengajuan(new \DateTime());

        $form = $this->createForm(PengajuanCutiType::class, $pengajuan, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            error_log("DEBUG: Form submitted");
            error_log("DEBUG: Form valid: " . ($form->isValid() ? 'YES' : 'NO'));
            error_log("DEBUG: Submit status: " . ($request->request->get('status') ?? 'NULL'));
            
            if (!$form->isValid()) {
                error_log("DEBUG: Form errors: " . json_encode($this->getFormErrors($form)));
                
                // Get hak cuti info for display
                $currentYear = (int) date('Y');
                $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
                $hakCutiUser = $hakCutiRepo->findByUserAndTahun($user, $currentYear);
                $hakCutiN1 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 1);
                $hakCutiN2 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 2);
                
                return $this->render('pengajuan_cuti/new.html.twig', [
                    'pengajuan' => $pengajuan,
                    'form' => $form,
                    'hak_cuti' => $hakCutiUser,
                    'hak_cuti_n1' => $hakCutiN1,
                    'hak_cuti_n2' => $hakCutiN2,
                ]);
            }
            
            // Validasi menggunakan CutiPolicy
            $validasi = $this->cutiPolicy->dapatAjukan(
                $user,
                $pengajuan->getJenisCuti(),
                $pengajuan->getTanggalMulai(),
                $pengajuan->getTanggalSelesai()
            );

            if (!$validasi->isValid()) {
                foreach ($validasi->getErrors() as $error) {
                    $this->addFlash('error', $error);
                }
                
                return $this->render('pengajuan_cuti/new.html.twig', [
                    'pengajuan' => $pengajuan,
                    'form' => $form,
                ]);
            }

            // Update user telepon jika diisi
            $userTelp = $form->get('userTelp')->getData();
            if ($userTelp && $userTelp !== $user->getTelp()) {
                $user->setTelp($userTelp);
                $this->entityManager->persist($user);
            }

            // Hitung lama cuti dan set status
            $lamaCuti = $this->cutiCalculator->hitungLamaCuti(
                $pengajuan->getTanggalMulai(),
                $pengajuan->getTanggalSelesai(),
                'workday'
            );
            $pengajuan->setLamaCuti($lamaCuti);
            
            // Check which button was clicked
            $submitStatus = $request->request->get('status');
            
            if ($submitStatus === 'submit') {
                // Apply workflow transition untuk ajukan cuti
                if ($this->pengajuanCutiWorkflow->can($pengajuan, 'ajukan')) {
                    $this->pengajuanCutiWorkflow->apply($pengajuan, 'ajukan');
                } else {
                    $this->addFlash('error', 'Tidak dapat mengajukan cuti dengan status saat ini');
                    // Get hak cuti info for display
                    $currentYear = (int) date('Y');
                    $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
                    $hakCutiUser = $hakCutiRepo->findByUserAndTahun($user, $currentYear);
                    $hakCutiN1 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 1);
                    $hakCutiN2 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 2);
                    
                    return $this->render('pengajuan_cuti/new.html.twig', [
                        'pengajuan' => $pengajuan,
                        'form' => $form,
                        'hak_cuti' => $hakCutiUser,
                        'hak_cuti_n1' => $hakCutiN1,
                        'hak_cuti_n2' => $hakCutiN2,
                    ]);
                }
            } else {
                // Simpan sebagai draft (status tetap draft)
                $pengajuan->setStatus('draft');
            }
            
            // Simpan pengajuan
            $this->entityManager->persist($pengajuan);
            $this->entityManager->flush();

            if ($submitStatus === 'submit') {
                $this->addFlash('success', 'Pengajuan cuti berhasil diajukan');
            } else {
                $this->addFlash('success', 'Pengajuan cuti berhasil disimpan sebagai draft');
            }
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        // Get hak cuti info for display
        $currentYear = (int) date('Y');
        $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
        $hakCutiUser = $hakCutiRepo->findByUserAndTahun($user, $currentYear);
        
        // Get previous years data
        $hakCutiN1 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 1);
        $hakCutiN2 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 2);
        
        return $this->render('pengajuan_cuti/new.html.twig', [
            'pengajuan' => $pengajuan,
            'form' => $form,
            'hak_cuti' => $hakCutiUser,
            'hak_cuti_n1' => $hakCutiN1,
            'hak_cuti_n2' => $hakCutiN2,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): Response
    {
        $pengajuan = $this->pengajuanCutiRepository->find($id);
        
        if (!$pengajuan) {
            $this->addFlash('error', 'Pengajuan cuti tidak ditemukan');
            return $this->redirectToRoute('pengajuan_cuti_index');
        }

        // Gunakan voter untuk cek akses
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_VIEW, $pengajuan);

        return $this->render('pengajuan_cuti/show.html.twig', [
            'pengajuan' => $pengajuan,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, int $id): Response
    {
        $pengajuan = $this->pengajuanCutiRepository->find($id);
        
        if (!$pengajuan) {
            $this->addFlash('error', 'Pengajuan cuti tidak ditemukan');
            return $this->redirectToRoute('pengajuan_cuti_index');
        }

        // Gunakan voter untuk cek akses edit
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_EDIT, $pengajuan);
        
        $user = $this->getUser();

        $form = $this->createForm(PengajuanCutiType::class, $pengajuan, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validasi ulang
            $validasi = $this->cutiPolicy->dapatAjukan(
                $user,
                $pengajuan->getJenisCuti(),
                $pengajuan->getTanggalMulai(),
                $pengajuan->getTanggalSelesai()
            );

            if (!$validasi->isValid()) {
                foreach ($validasi->getErrors() as $error) {
                    $this->addFlash('error', $error);
                }
                
                return $this->render('pengajuan_cuti/edit.html.twig', [
                    'pengajuan' => $pengajuan,
                    'form' => $form,
                ]);
            }

            // Update user telepon jika diisi
            $userTelp = $form->get('userTelp')->getData();
            if ($userTelp && $userTelp !== $user->getTelp()) {
                $user->setTelp($userTelp);
                $this->entityManager->persist($user);
            }

            // Update data
            $lamaCuti = $this->cutiCalculator->hitungLamaCuti(
                $pengajuan->getTanggalMulai(),
                $pengajuan->getTanggalSelesai(),
                'workday'
            );
            $pengajuan->setLamaCuti($lamaCuti);
            $pengajuan->setTanggalPengajuan(new \DateTime());
            
            // Apply workflow transition if submitting
            if ($this->pengajuanCutiWorkflow->can($pengajuan, 'ajukan')) {
                // Debug: log before transition
                error_log("DEBUG EDIT: Before transition - Status: " . $pengajuan->getStatus());
                
                $this->pengajuanCutiWorkflow->apply($pengajuan, 'ajukan');
                
                // Debug: log after transition
                error_log("DEBUG EDIT: After transition - Status: " . $pengajuan->getStatus());
            } else {
                error_log("DEBUG EDIT: Cannot apply 'ajukan' transition. Current status: " . $pengajuan->getStatus());
            }

            // Catat riwayat
            $this->catatRiwayat($pengajuan, 'diajukan', 'Pengajuan cuti diperbarui dan disubmit ulang', $user);
            
            $this->entityManager->flush();

            $this->addFlash('success', 'Pengajuan cuti berhasil diperbarui');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        // Get hak cuti info for display
        $currentYear = (int) date('Y');
        $hakCutiRepo = $this->entityManager->getRepository(\App\Entity\HakCuti::class);
        $hakCutiUser = $hakCutiRepo->findByUserAndTahun($user, $currentYear);
        
        // Get previous years data
        $hakCutiN1 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 1);
        $hakCutiN2 = $hakCutiRepo->findByUserAndTahun($user, $currentYear - 2);

        return $this->render('pengajuan_cuti/edit.html.twig', [
            'pengajuan' => $pengajuan,
            'form' => $form,
            'hak_cuti' => $hakCutiUser,
            'hak_cuti_n1' => $hakCutiN1,
            'hak_cuti_n2' => $hakCutiN2,
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_APPROVER')]
    public function approve(Request $request, PengajuanCuti $pengajuan): Response
    {
        // Gunakan voter untuk cek akses approve
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_APPROVE, $pengajuan);
        
        $user = $this->getUser();

        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('approve_' . $pengajuan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        // Apply workflow transition
        if ($this->pengajuanCutiWorkflow->can($pengajuan, 'setujui')) {
            $this->pengajuanCutiWorkflow->apply($pengajuan, 'setujui');
            
            // Set pejabat penyetuju
            if (method_exists($pengajuan, 'setPejabatPenyetuju')) {
                // Assuming user has relation to Pejabat
                $pengajuan->setPejabatPenyetuju($this->getPejabatFromUser($user));
            }

            // Kurangi saldo cuti jika cuti tahunan
            if ($pengajuan->getJenisCuti()->getKode() === 'CT') {
                $this->cutiCalculator->kurangiSaldoTahunan(
                    $pengajuan->getUser(),
                    (int) $pengajuan->getTanggalMulai()->format('Y'),
                    $pengajuan->getLamaCuti()
                );
            }

            // Generate PDF document
            try {
                $this->dokumenCutiGenerator->generate($pengajuan);
            } catch (\Exception $e) {
                // Log error but don't fail the approval process
                error_log('Failed to generate PDF document: ' . $e->getMessage());
            }

            // Catat riwayat
            $this->catatRiwayat($pengajuan, 'disetujui', 'Pengajuan cuti disetujui', $user);
        } else {
            $this->addFlash('error', 'Tidak dapat menyetujui cuti pada status saat ini');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Pengajuan cuti berhasil disetujui');
        return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_APPROVER')]
    public function reject(Request $request, PengajuanCuti $pengajuan): Response
    {
        // Gunakan voter untuk cek akses reject
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_REJECT, $pengajuan);
        
        $user = $this->getUser();

        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('reject_' . $pengajuan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        $alasan = $request->request->get('alasan_penolakan', '');
        if (empty($alasan)) {
            $this->addFlash('error', 'Alasan penolakan harus diisi');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        // Apply workflow transition
        if ($this->pengajuanCutiWorkflow->can($pengajuan, 'tolak')) {
            $this->pengajuanCutiWorkflow->apply($pengajuan, 'tolak');
            
            // Set pejabat penyetuju
            if (method_exists($pengajuan, 'setPejabatPenyetuju')) {
                $pengajuan->setPejabatPenyetuju($this->getPejabatFromUser($user));
            }

            // Catat riwayat dengan alasan penolakan
            $this->catatRiwayat($pengajuan, 'ditolak', "Pengajuan cuti ditolak: {$alasan}", $user);
        } else {
            $this->addFlash('error', 'Tidak dapat menolak cuti pada status saat ini');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Pengajuan cuti berhasil ditolak');
        return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Request $request, PengajuanCuti $pengajuan): Response
    {
        // Gunakan voter untuk cek akses cancel
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_CANCEL, $pengajuan);
        
        $user = $this->getUser();

        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('cancel_' . $pengajuan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        // Apply workflow transition
        if ($this->pengajuanCutiWorkflow->can($pengajuan, 'batalkan')) {
            $this->pengajuanCutiWorkflow->apply($pengajuan, 'batalkan');
            
            // Catat riwayat
            $this->catatRiwayat($pengajuan, 'dibatalkan', 'Pengajuan cuti dibatalkan oleh pemohon', $user);
        } else {
            $this->addFlash('error', 'Tidak dapat membatalkan cuti pada status saat ini');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Pengajuan cuti berhasil dibatalkan');
        return $this->redirectToRoute('pengajuan_cuti_index');
    }


    /**
     * Catat riwayat perubahan status
     */
    private function catatRiwayat(PengajuanCuti $pengajuan, string $status, string $keterangan, $user): void
    {
        $riwayat = new RiwayatCuti();
        $riwayat->setPengajuanCuti($pengajuan);
        $riwayat->setAksi($status);
        $riwayat->setCatatan($keterangan);
        $riwayat->setTanggalAksi(new \DateTimeImmutable());
        $riwayat->setUser($user);

        $this->entityManager->persist($riwayat);
    }

    /**
     * Get Pejabat from User (assuming relationship exists)
     */
    private function getPejabatFromUser($user): ?object
    {
        // This is a placeholder - implement based on your user-pejabat relationship
        // For now, return null since we don't have the exact relationship structure
        return null;
    }

    /**
     * Helper method to get form errors for debugging
     */
    private function getFormErrors($form): array
    {
        $errors = [];
        
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof \Symfony\Component\Form\FormInterface) {
                $childErrors = $this->getFormErrors($childForm);
                if (!empty($childErrors)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        
        return $errors;
    }

    /**
     * Download PDF document
     */
    #[Route('/{id}/download-pdf', name: 'download_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPdf(PengajuanCuti $pengajuan): Response
    {
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_VIEW, $pengajuan);
        
        // Generate PDF if not exists
        if (!$this->dokumenCutiGenerator->hasDocument($pengajuan)) {
            if ($pengajuan->getStatus() !== 'disetujui') {
                $this->addFlash('error', 'Dokumen PDF hanya tersedia untuk cuti yang sudah disetujui');
                return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
            }
            
            try {
                $this->dokumenCutiGenerator->generate($pengajuan);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Gagal menggenerate dokumen PDF: ' . $e->getMessage());
                return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
            }
        }

        $filePath = $this->dokumenCutiGenerator->getDocumentPath($pengajuan);
        
        if (!$filePath || !file_exists($filePath)) {
            $this->addFlash('error', 'File dokumen tidak ditemukan');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        $fileName = sprintf(
            'Cuti_%s_%s.pdf',
            $pengajuan->getUser()->getNama(),
            $pengajuan->getTanggalMulai()->format('Y-m-d')
        );

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;
    }

    /**
     * Generate PDF document manually
     */
    #[Route('/{id}/generate-pdf', name: 'generate_pdf', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generatePdf(Request $request, PengajuanCuti $pengajuan): Response
    {
        $this->denyAccessUnlessGranted(PengajuanCutiVoter::ATTR_VIEW, $pengajuan);
        
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('generate_pdf_' . $pengajuan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }
        
        if ($pengajuan->getStatus() !== 'disetujui') {
            $this->addFlash('error', 'Dokumen PDF hanya dapat dibuat untuk cuti yang sudah disetujui');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        try {
            $this->dokumenCutiGenerator->regenerate($pengajuan);
            $this->addFlash('success', 'Dokumen PDF berhasil dibuat/diperbaharui');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Gagal menggenerate dokumen PDF: ' . $e->getMessage());
        }

        return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
    }

    /**
     * Set nomor surat for approved leave request
     */
    #[Route('/{id}/set-nomor-surat', name: 'set_nomor_surat', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function setNomorSurat(Request $request, PengajuanCuti $pengajuan): Response
    {
        if ($pengajuan->getStatus() !== 'disetujui') {
            $this->addFlash('error', 'Nomor surat hanya dapat diset untuk cuti yang sudah disetujui');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('set_nomor_surat_' . $pengajuan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        $nomorSurat = $request->request->get('nomor_surat', '');
        if (empty($nomorSurat)) {
            $this->addFlash('error', 'Nomor surat harus diisi');
            return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
        }

        $pengajuan->setNomorSurat($nomorSurat);
        $this->entityManager->flush();

        $this->addFlash('success', 'Nomor surat berhasil disimpan');
        return $this->redirectToRoute('pengajuan_cuti_show', ['id' => $pengajuan->getId()]);
    }

    /**
     * Configuration management for admin
     */
    #[Route('/konfigurasi', name: 'konfigurasi', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function konfigurasi(Request $request): Response
    {
        $kepalaKantor = $this->konfigurasiRepository->getKepalaKantor();

        if ($request->isMethod('POST')) {
            // Validasi CSRF token
            if (!$this->isCsrfTokenValid('konfigurasi', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token tidak valid');
                return $this->redirectToRoute('pengajuan_cuti_konfigurasi');
            }

            $nama = $request->request->get('kepala_kantor_nama', '');
            $nip = $request->request->get('kepala_kantor_nip', '');
            $jabatan = $request->request->get('kepala_kantor_jabatan', '');

            if (empty($nama) || empty($nip)) {
                $this->addFlash('error', 'Nama dan NIP Kepala Kantor harus diisi');
                return $this->redirectToRoute('pengajuan_cuti_konfigurasi');
            }

            // Save configuration
            $this->konfigurasiRepository->setValue('kepala_kantor_nama', $nama, 'Nama Kepala Kantor');
            $this->konfigurasiRepository->setValue('kepala_kantor_nip', $nip, 'NIP Kepala Kantor');
            $this->konfigurasiRepository->setValue('kepala_kantor_jabatan', $jabatan ?: 'Kepala Kanwil Kemenag Sulbar', 'Jabatan Kepala Kantor');

            $this->addFlash('success', 'Konfigurasi berhasil disimpan');
            return $this->redirectToRoute('pengajuan_cuti_konfigurasi');
        }

        return $this->render('pengajuan_cuti/konfigurasi.html.twig', [
            'kepala_kantor' => $kepalaKantor,
        ]);
    }
}