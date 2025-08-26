<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\JenisCuti;
use App\Form\JenisCutiType;
use App\Repository\JenisCutiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/jenis-cuti', name: 'admin_jenis_cuti_')]
#[IsGranted('ROLE_ADMIN')]
class JenisCutiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JenisCutiRepository $jenisCutiRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $jenisCutiList = $this->jenisCutiRepository->findBy([], ['nama' => 'ASC']);

        return $this->render('admin/jenis_cuti/index.html.twig', [
            'jenis_cuti_list' => $jenisCutiList,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $jenisCuti = new JenisCuti();
        $form = $this->createForm(JenisCutiType::class, $jenisCuti);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi kode
            $existing = $this->jenisCutiRepository->findOneBy(['kode' => $jenisCuti->getKode()]);
            if ($existing) {
                $this->addFlash('error', 'Kode jenis cuti sudah digunakan');
                return $this->render('admin/jenis_cuti/new.html.twig', [
                    'jenis_cuti' => $jenisCuti,
                    'form' => $form,
                ]);
            }

            $this->entityManager->persist($jenisCuti);
            $this->entityManager->flush();

            $this->addFlash('success', 'Jenis cuti berhasil ditambahkan');
            return $this->redirectToRoute('admin_jenis_cuti_index');
        }

        return $this->render('admin/jenis_cuti/new.html.twig', [
            'jenis_cuti' => $jenisCuti,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(JenisCuti $jenisCuti): Response
    {
        return $this->render('admin/jenis_cuti/show.html.twig', [
            'jenis_cuti' => $jenisCuti,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, JenisCuti $jenisCuti): Response
    {
        $form = $this->createForm(JenisCutiType::class, $jenisCuti);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi kode (kecuali untuk record saat ini)
            $existing = $this->jenisCutiRepository->findOneBy(['kode' => $jenisCuti->getKode()]);
            if ($existing && $existing->getId() !== $jenisCuti->getId()) {
                $this->addFlash('error', 'Kode jenis cuti sudah digunakan');
                return $this->render('admin/jenis_cuti/edit.html.twig', [
                    'jenis_cuti' => $jenisCuti,
                    'form' => $form,
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Jenis cuti berhasil diperbarui');
            return $this->redirectToRoute('admin_jenis_cuti_show', ['id' => $jenisCuti->getId()]);
        }

        return $this->render('admin/jenis_cuti/edit.html.twig', [
            'jenis_cuti' => $jenisCuti,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, JenisCuti $jenisCuti): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('delete_' . $jenisCuti->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_jenis_cuti_show', ['id' => $jenisCuti->getId()]);
        }

        // Cek apakah masih ada pengajuan yang menggunakan jenis cuti ini
        if (!$jenisCuti->getPengajuanCutis()->isEmpty()) {
            $this->addFlash('error', 'Tidak dapat menghapus jenis cuti yang masih digunakan dalam pengajuan');
            return $this->redirectToRoute('admin_jenis_cuti_show', ['id' => $jenisCuti->getId()]);
        }

        $nama = $jenisCuti->getNama();
        $this->entityManager->remove($jenisCuti);
        $this->entityManager->flush();

        $this->addFlash('success', "Jenis cuti '{$nama}' berhasil dihapus");
        return $this->redirectToRoute('admin_jenis_cuti_index');
    }

    #[Route('/{id}/toggle-active', name: 'toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(Request $request, JenisCuti $jenisCuti): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('toggle_active_' . $jenisCuti->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_jenis_cuti_show', ['id' => $jenisCuti->getId()]);
        }

        $jenisCuti->setAktif(!$jenisCuti->isAktif());
        $this->entityManager->flush();

        $status = $jenisCuti->isAktif() ? 'diaktifkan' : 'dinonaktifkan';
        $this->addFlash('success', "Jenis cuti '{$jenisCuti->getNama()}' berhasil {$status}");
        
        return $this->redirectToRoute('admin_jenis_cuti_show', ['id' => $jenisCuti->getId()]);
    }
}