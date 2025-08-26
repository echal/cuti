<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PengajuanCuti;
use App\Entity\User;
use App\Form\PengajuanCutiTypeSimple;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FormExampleController extends AbstractController
{
    #[Route('/pengajuan-cuti/baru', name: 'pengajuan_cuti_new', methods: ['GET', 'POST'])]
    public function newPengajuanCuti(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        // Get current user (in real app, this would come from security context)
        // For demo, we'll get the first user from fixtures
        $currentUser = $userRepository->findOneBy(['nama' => 'Siti Aminah, S.Ag']);
        
        if (!$currentUser) {
            throw $this->createNotFoundException('Demo user tidak ditemukan. Jalankan fixtures terlebih dahulu.');
        }

        $pengajuanCuti = new PengajuanCuti();
        $pengajuanCuti->setUser($currentUser);

        // Create form with user context for dynamic jenisCuti filtering
        $form = $this->createForm(PengajuanCutiTypeSimple::class, $pengajuanCuti, [
            'user' => $currentUser
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload if provided
            $uploadedFile = $form->get('filePendukung')->getData();
            if ($uploadedFile) {
                // In real implementation, you would save the file and set the path
                // For demo, we'll just set a placeholder
                $pengajuanCuti->setFilePendukung($uploadedFile->getClientOriginalName());
            }

            $entityManager->persist($pengajuanCuti);
            $entityManager->flush();

            $this->addFlash('success', 'Pengajuan cuti berhasil dibuat!');

            return $this->redirectToRoute('pengajuan_cuti_show', [
                'id' => $pengajuanCuti->getId()
            ]);
        }

        return $this->render('form_example/pengajuan_cuti_form.html.twig', [
            'form' => $form,
            'user' => $currentUser,
            'title' => 'Buat Pengajuan Cuti Baru'
        ]);
    }

    #[Route('/pengajuan-cuti/{id}', name: 'pengajuan_cuti_show', methods: ['GET'])]
    public function showPengajuanCuti(PengajuanCuti $pengajuanCuti): Response
    {
        return $this->render('form_example/pengajuan_cuti_show.html.twig', [
            'pengajuan_cuti' => $pengajuanCuti
        ]);
    }
}