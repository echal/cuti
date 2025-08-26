<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UnitKerja;
use App\Form\UnitKerjaType;
use App\Repository\UnitKerjaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/unit-kerja', name: 'admin_unit_kerja_')]
#[IsGranted('ROLE_ADMIN')]
class UnitKerjaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UnitKerjaRepository $unitKerjaRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');

        $queryBuilder = $this->unitKerjaRepository->createQueryBuilder('uk')
            ->orderBy('uk.nama', 'ASC');

        if ($search) {
            $queryBuilder
                ->andWhere('uk.nama LIKE :search OR uk.kode LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $units = $queryBuilder->getQuery()->getResult();

        return $this->render('admin/unit_kerja/index.html.twig', [
            'units' => $units,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $unitKerja = new UnitKerja();
        $form = $this->createForm(UnitKerjaType::class, $unitKerja);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi kode
            $existing = $this->unitKerjaRepository->findOneBy(['kode' => $unitKerja->getKode()]);
            if ($existing) {
                $this->addFlash('error', 'Kode unit kerja sudah digunakan');
                return $this->render('admin/unit_kerja/new.html.twig', [
                    'unit_kerja' => $unitKerja,
                    'form' => $form,
                ]);
            }

            $this->entityManager->persist($unitKerja);
            $this->entityManager->flush();

            $this->addFlash('success', 'Unit kerja berhasil ditambahkan');
            return $this->redirectToRoute('admin_unit_kerja_index');
        }

        return $this->render('admin/unit_kerja/new.html.twig', [
            'unit_kerja' => $unitKerja,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(UnitKerja $unitKerja): Response
    {
        return $this->render('admin/unit_kerja/show.html.twig', [
            'unit_kerja' => $unitKerja,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, UnitKerja $unitKerja): Response
    {
        $form = $this->createForm(UnitKerjaType::class, $unitKerja);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi kode (kecuali untuk record saat ini)
            $existing = $this->unitKerjaRepository->findOneBy(['kode' => $unitKerja->getKode()]);
            if ($existing && $existing->getId() !== $unitKerja->getId()) {
                $this->addFlash('error', 'Kode unit kerja sudah digunakan');
                return $this->render('admin/unit_kerja/edit.html.twig', [
                    'unit_kerja' => $unitKerja,
                    'form' => $form,
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Unit kerja berhasil diperbarui');
            return $this->redirectToRoute('admin_unit_kerja_show', ['id' => $unitKerja->getId()]);
        }

        return $this->render('admin/unit_kerja/edit.html.twig', [
            'unit_kerja' => $unitKerja,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, UnitKerja $unitKerja): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('delete_' . $unitKerja->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_unit_kerja_show', ['id' => $unitKerja->getId()]);
        }

        // Cek apakah masih ada user yang menggunakan unit kerja ini
        if (!$unitKerja->getUsers()->isEmpty()) {
            $this->addFlash('error', 'Tidak dapat menghapus unit kerja yang masih digunakan oleh user');
            return $this->redirectToRoute('admin_unit_kerja_show', ['id' => $unitKerja->getId()]);
        }

        $nama = $unitKerja->getNama();
        $this->entityManager->remove($unitKerja);
        $this->entityManager->flush();

        $this->addFlash('success', "Unit kerja '{$nama}' berhasil dihapus");
        return $this->redirectToRoute('admin_unit_kerja_index');
    }
}