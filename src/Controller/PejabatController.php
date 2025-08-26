<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Pejabat;
use App\Form\PejabatType;
use App\Repository\PejabatRepository;
use App\Repository\UnitKerjaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pejabat', name: 'admin_pejabat_')]
#[IsGranted('ROLE_ADMIN')]
class PejabatController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PejabatRepository $pejabatRepository,
        private readonly UnitKerjaRepository $unitKerjaRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $unitKerjaId = $request->query->get('unit_kerja');
        $status = $request->query->get('status');

        $queryBuilder = $this->pejabatRepository->createQueryBuilder('p')
            ->leftJoin('p.unitKerja', 'uk')
            ->orderBy('p.nama', 'ASC');

        if ($search) {
            $queryBuilder
                ->andWhere('p.nama LIKE :search OR p.nip LIKE :search OR p.jabatan LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($unitKerjaId) {
            $queryBuilder
                ->andWhere('p.unitKerja = :unitKerja')
                ->setParameter('unitKerja', $unitKerjaId);
        }

        if ($status) {
            $queryBuilder
                ->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        $pejabats = $queryBuilder->getQuery()->getResult();
        $units = $this->unitKerjaRepository->findBy([], ['nama' => 'ASC']);

        return $this->render('admin/pejabat/index.html.twig', [
            'pejabats' => $pejabats,
            'units' => $units,
            'search' => $search,
            'unit_kerja_id' => $unitKerjaId,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $pejabat = new Pejabat();
        $form = $this->createForm(PejabatType::class, $pejabat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi NIP jika ada
            if ($pejabat->getNip()) {
                $existing = $this->pejabatRepository->findOneBy(['nip' => $pejabat->getNip()]);
                if ($existing) {
                    $this->addFlash('error', 'NIP sudah digunakan oleh pejabat lain');
                    return $this->render('admin/pejabat/new.html.twig', [
                        'pejabat' => $pejabat,
                        'form' => $form,
                    ]);
                }
            }

            $this->entityManager->persist($pejabat);
            $this->entityManager->flush();

            $this->addFlash('success', 'Pejabat berhasil ditambahkan');
            return $this->redirectToRoute('admin_pejabat_index');
        }

        return $this->render('admin/pejabat/new.html.twig', [
            'pejabat' => $pejabat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Pejabat $pejabat): Response
    {
        return $this->render('admin/pejabat/show.html.twig', [
            'pejabat' => $pejabat,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Pejabat $pejabat): Response
    {
        $form = $this->createForm(PejabatType::class, $pejabat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi NIP (kecuali untuk record saat ini)
            if ($pejabat->getNip()) {
                $existing = $this->pejabatRepository->findOneBy(['nip' => $pejabat->getNip()]);
                if ($existing && $existing->getId() !== $pejabat->getId()) {
                    $this->addFlash('error', 'NIP sudah digunakan oleh pejabat lain');
                    return $this->render('admin/pejabat/edit.html.twig', [
                        'pejabat' => $pejabat,
                        'form' => $form,
                    ]);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Pejabat berhasil diperbarui');
            return $this->redirectToRoute('admin_pejabat_show', ['id' => $pejabat->getId()]);
        }

        return $this->render('admin/pejabat/edit.html.twig', [
            'pejabat' => $pejabat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Pejabat $pejabat): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('delete_' . $pejabat->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_pejabat_show', ['id' => $pejabat->getId()]);
        }

        // Cek apakah masih ada pengajuan yang menggunakan pejabat ini
        if (!$pejabat->getPengajuanCutisPenyetuju()->isEmpty() || !$pejabat->getPengajuanCutisAtasan()->isEmpty()) {
            $this->addFlash('error', 'Tidak dapat menghapus pejabat yang masih terkait dengan pengajuan cuti');
            return $this->redirectToRoute('admin_pejabat_show', ['id' => $pejabat->getId()]);
        }

        $nama = $pejabat->getNama();
        $this->entityManager->remove($pejabat);
        $this->entityManager->flush();

        $this->addFlash('success', "Pejabat '{$nama}' berhasil dihapus");
        return $this->redirectToRoute('admin_pejabat_index');
    }
}