<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\HariLibur;
use App\Form\HariLiburType;
use App\Repository\HariLiburRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/hari-libur')]
#[IsGranted('ROLE_ADMIN')]
class HariLiburController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HariLiburRepository $hariLiburRepository
    ) {
    }

    #[Route('/', name: 'admin_hari_libur_index', methods: ['GET'])]
    public function index(): Response
    {
        $currentYear = (int) date('Y');
        $hariLiburList = $this->hariLiburRepository->findBy([], ['tanggal' => 'ASC']);
        
        // Group by year for better display
        $hariLiburByYear = [];
        foreach ($hariLiburList as $hariLibur) {
            $year = $hariLibur->getTanggal()->format('Y');
            $hariLiburByYear[$year][] = $hariLibur;
        }

        return $this->render('admin/hari_libur/index.html.twig', [
            'hari_libur_by_year' => $hariLiburByYear,
            'current_year' => $currentYear,
        ]);
    }

    #[Route('/new', name: 'admin_hari_libur_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $hariLibur = new HariLibur();
        $form = $this->createForm(HariLiburType::class, $hariLibur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if date already exists
            $existingHariLibur = $this->hariLiburRepository->findOneBy([
                'tanggal' => $hariLibur->getTanggal()
            ]);

            if ($existingHariLibur) {
                $this->addFlash('error', 'Tanggal libur tersebut sudah terdaftar');
                return $this->render('admin/hari_libur/new.html.twig', [
                    'hari_libur' => $hariLibur,
                    'form' => $form,
                ]);
            }

            $this->entityManager->persist($hariLibur);
            $this->entityManager->flush();

            $this->addFlash('success', 'Hari libur berhasil ditambahkan');

            return $this->redirectToRoute('admin_hari_libur_index');
        }

        return $this->render('admin/hari_libur/new.html.twig', [
            'hari_libur' => $hariLibur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_hari_libur_show', methods: ['GET'])]
    public function show(HariLibur $hariLibur): Response
    {
        return $this->render('admin/hari_libur/show.html.twig', [
            'hari_libur' => $hariLibur,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_hari_libur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HariLibur $hariLibur): Response
    {
        $form = $this->createForm(HariLiburType::class, $hariLibur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if date already exists (exclude current record)
            $existingHariLibur = $this->hariLiburRepository->createQueryBuilder('h')
                ->where('h.tanggal = :tanggal')
                ->andWhere('h.id != :id')
                ->setParameter('tanggal', $hariLibur->getTanggal())
                ->setParameter('id', $hariLibur->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingHariLibur) {
                $this->addFlash('error', 'Tanggal libur tersebut sudah terdaftar');
                return $this->render('admin/hari_libur/edit.html.twig', [
                    'hari_libur' => $hariLibur,
                    'form' => $form,
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Hari libur berhasil diperbarui');

            return $this->redirectToRoute('admin_hari_libur_index');
        }

        return $this->render('admin/hari_libur/edit.html.twig', [
            'hari_libur' => $hariLibur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_hari_libur_delete', methods: ['POST'])]
    public function delete(Request $request, HariLibur $hariLibur): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hariLibur->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($hariLibur);
            $this->entityManager->flush();

            $this->addFlash('success', 'Hari libur berhasil dihapus');
        }

        return $this->redirectToRoute('admin_hari_libur_index');
    }

    #[Route('/bulk/import-default', name: 'admin_hari_libur_import_default', methods: ['POST'])]
    public function importDefault(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('import_default', $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token keamanan tidak valid');
            return $this->redirectToRoute('admin_hari_libur_index');
        }

        $year = (int) ($request->getPayload()->getString('year') ?: date('Y'));
        
        // Default holidays for Indonesia (example for current year)
        $defaultHolidays = $this->getDefaultIndonesianHolidays($year);
        
        $imported = 0;
        foreach ($defaultHolidays as $date => $description) {
            $existingHariLibur = $this->hariLiburRepository->findOneBy([
                'tanggal' => new \DateTime($date)
            ]);

            if (!$existingHariLibur) {
                $hariLibur = new HariLibur();
                $hariLibur->setTanggal(new \DateTime($date));
                $hariLibur->setKeterangan($description);

                $this->entityManager->persist($hariLibur);
                $imported++;
            }
        }

        if ($imported > 0) {
            $this->entityManager->flush();
            $this->addFlash('success', "Berhasil mengimpor {$imported} hari libur tahun {$year}");
        } else {
            $this->addFlash('info', "Semua hari libur tahun {$year} sudah terdaftar");
        }

        return $this->redirectToRoute('admin_hari_libur_index');
    }

    /**
     * Get default Indonesian holidays for a given year
     * Note: This is a simplified example. In real implementation,
     * you should use a proper calendar API or library for accurate Islamic holidays
     */
    private function getDefaultIndonesianHolidays(int $year): array
    {
        return [
            "{$year}-01-01" => "Tahun Baru",
            "{$year}-02-12" => "Tahun Baru Imlek", // Example - varies each year
            "{$year}-03-22" => "Hari Raya Nyepi", // Example - varies each year
            "{$year}-04-29" => "Wafat Isa Al-Masih", // Example - varies each year
            "{$year}-05-01" => "Hari Buruh Internasional",
            "{$year}-05-09" => "Kenaikan Isa Al-Masih", // Example - varies each year
            "{$year}-06-01" => "Hari Lahir Pancasila",
            "{$year}-08-17" => "Hari Kemerdekaan RI",
            "{$year}-12-25" => "Hari Raya Natal",
            // Note: Islamic holidays like Idul Fitri, Idul Adha, etc. vary each year
            // and should be calculated using proper Islamic calendar
        ];
    }
}