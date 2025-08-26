<?php

namespace App\Repository;

use App\Entity\DokumenCuti;
use App\Entity\PengajuanCuti;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DokumenCuti>
 */
class DokumenCutiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DokumenCuti::class);
    }

    public function save(DokumenCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DokumenCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByPengajuanCuti(PengajuanCuti $pengajuanCuti): ?DokumenCuti
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.pengajuanCuti = :pengajuanCuti')
            ->setParameter('pengajuanCuti', $pengajuanCuti)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNamaFile(string $namaFile): array
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.namaFile LIKE :namaFile')
            ->setParameter('namaFile', '%' . $namaFile . '%')
            ->orderBy('dc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFileExtension(string $extension): array
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.namaFile LIKE :extension')
            ->setParameter('extension', '%.' . $extension)
            ->orderBy('dc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentDocuments(int $limit = 10): array
    {
        return $this->createQueryBuilder('dc')
            ->join('dc.pengajuanCuti', 'pc')
            ->join('pc.user', 'u')
            ->orderBy('dc.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('dc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findImageDocuments(): array
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.namaFile LIKE :jpg OR dc.namaFile LIKE :jpeg OR dc.namaFile LIKE :png OR dc.namaFile LIKE :gif OR dc.namaFile LIKE :bmp OR dc.namaFile LIKE :webp')
            ->setParameter('jpg', '%.jpg')
            ->setParameter('jpeg', '%.jpeg')
            ->setParameter('png', '%.png')
            ->setParameter('gif', '%.gif')
            ->setParameter('bmp', '%.bmp')
            ->setParameter('webp', '%.webp')
            ->orderBy('dc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPdfDocuments(): array
    {
        return $this->findByFileExtension('pdf');
    }

    public function countTotalDocuments(): int
    {
        return (int) $this->createQueryBuilder('dc')
            ->select('COUNT(dc.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getFileTypeStatistics(): array
    {
        $documents = $this->findAll();
        $statistics = [];

        foreach ($documents as $document) {
            $extension = $document->getFileExtension();
            if (!isset($statistics[$extension])) {
                $statistics[$extension] = 0;
            }
            $statistics[$extension]++;
        }

        arsort($statistics);
        return $statistics;
    }

    public function findOrphanedDocuments(): array
    {
        return $this->createQueryBuilder('dc')
            ->leftJoin('dc.pengajuanCuti', 'pc')
            ->andWhere('pc.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findDocumentsWithMissingFiles(): array
    {
        $allDocuments = $this->findAll();
        $missingFiles = [];

        foreach ($allDocuments as $document) {
            if (!$document->fileExists()) {
                $missingFiles[] = $document;
            }
        }

        return $missingFiles;
    }

    public function getTotalFileSize(): int
    {
        $documents = $this->findAll();
        $totalSize = 0;

        foreach ($documents as $document) {
            $size = $document->getFileSize();
            if ($size !== null) {
                $totalSize += $size;
            }
        }

        return $totalSize;
    }

    public function getFormattedTotalFileSize(): string
    {
        $totalSize = $this->getTotalFileSize();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($totalSize >= 1024 && $unitIndex < count($units) - 1) {
            $totalSize /= 1024;
            $unitIndex++;
        }

        return round($totalSize, 2) . ' ' . $units[$unitIndex];
    }

    public function findByMonthAndYear(int $month, int $year): array
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('MONTH(dc.createdAt) = :month')
            ->andWhere('YEAR(dc.createdAt) = :year')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->orderBy('dc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUploadStatisticsByMonth(int $year): array
    {
        $result = $this->createQueryBuilder('dc')
            ->select('MONTH(dc.createdAt) as bulan, COUNT(dc.id) as total')
            ->andWhere('YEAR(dc.createdAt) = :year')
            ->setParameter('year', $year)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->getQuery()
            ->getResult();

        $statistics = array_fill(1, 12, 0);
        foreach ($result as $row) {
            $statistics[(int) $row['bulan']] = (int) $row['total'];
        }

        return $statistics;
    }
}