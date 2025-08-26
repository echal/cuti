<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\HariLiburRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/hari-libur')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class HariLiburApiController extends AbstractController
{
    public function __construct(
        private readonly HariLiburRepository $hariLiburRepository
    ) {
    }

    #[Route('/by-year/{year}', name: 'api_hari_libur_by_year', methods: ['GET'])]
    public function getHariLiburByYear(int $year): JsonResponse
    {
        $hariLiburArray = $this->hariLiburRepository->getTanggalLiburArray($year);
        
        return new JsonResponse($hariLiburArray);
    }

    #[Route('/check/{date}', name: 'api_hari_libur_check', methods: ['GET'])]
    public function checkHariLibur(string $date): JsonResponse
    {
        try {
            $dateObj = new \DateTime($date);
            $isHariLibur = $this->hariLiburRepository->isHariLibur($dateObj);
            
            return new JsonResponse([
                'date' => $date,
                'is_holiday' => $isHariLibur
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Invalid date format. Use YYYY-MM-DD.'
            ], 400);
        }
    }

    #[Route('/range/{startDate}/{endDate}', name: 'api_hari_libur_range', methods: ['GET'])]
    public function getHariLiburInRange(string $startDate, string $endDate): JsonResponse
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            
            $hariLiburList = $this->hariLiburRepository->findByDateRange($start, $end);
            
            $result = [];
            foreach ($hariLiburList as $hariLibur) {
                $result[] = [
                    'date' => $hariLibur->getTanggal()->format('Y-m-d'),
                    'description' => $hariLibur->getKeterangan()
                ];
            }
            
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Invalid date format. Use YYYY-MM-DD.'
            ], 400);
        }
    }
}