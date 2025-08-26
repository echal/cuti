<?php

namespace App\Repository;

use App\Entity\HariLibur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HariLibur>
 */
class HariLiburRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HariLibur::class);
    }

    /**
     * Find all hari libur dalam rentang tanggal tertentu
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return HariLibur[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.tanggal >= :start')
            ->andWhere('h.tanggal <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('h.tanggal', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check apakah tanggal tertentu adalah hari libur
     */
    public function isHariLibur(\DateTimeInterface $tanggal): bool
    {
        $result = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.tanggal = :tanggal')
            ->setParameter('tanggal', $tanggal->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Get all hari libur dalam tahun tertentu
     * 
     * @param int $tahun
     * @return HariLibur[]
     */
    public function findByTahun(int $tahun): array
    {
        $startDate = new \DateTime($tahun . '-01-01');
        $endDate = new \DateTime($tahun . '-12-31');

        return $this->findByDateRange($startDate, $endDate);
    }

    /**
     * Get tanggal-tanggal libur dalam bentuk array string untuk JavaScript
     * 
     * @param int $tahun
     * @return string[]
     */
    public function getTanggalLiburArray(int $tahun): array
    {
        $hariLibur = $this->findByTahun($tahun);
        return array_map(fn($item) => $item->getTanggal()->format('Y-m-d'), $hariLibur);
    }
}
