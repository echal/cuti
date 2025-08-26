<?php

namespace App\Repository;

use App\Entity\PengajuanCuti;
use App\Entity\User;
use App\Entity\UnitKerja;
use App\Entity\Pejabat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PengajuanCuti>
 */
class PengajuanCutiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PengajuanCuti::class);
    }

    public function save(PengajuanCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PengajuanCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.status = :status')
            ->setParameter('status', $status)
            ->join('pc.user', 'u')
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingApproval(): array
    {
        return $this->findByStatus('diajukan');
    }

    public function findByPejabatPenyetuju(Pejabat $pejabat): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.pejabatPenyetuju = :pejabat')
            ->setParameter('pejabat', $pejabat)
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.tanggalMulai <= :endDate')
            ->andWhere('pc.tanggalSelesai >= :startDate')
            ->andWhere('pc.status = :status')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'disetujui')
            ->join('pc.user', 'u')
            ->orderBy('pc.tanggalMulai', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentMonth(): array
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        return $this->findByDateRange($startOfMonth, $endOfMonth);
    }

    public function findExpiredDrafts(int $daysOld = 30): array
    {
        $cutoffDate = new \DateTime("-{$daysOld} days");
        
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.status = :status')
            ->andWhere('pc.createdAt <= :cutoffDate')
            ->setParameter('status', 'draft')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('pc.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndYear(User $user, int $year): array
    {
        $startDate = new \DateTime("{$year}-01-01");
        $endDate = new \DateTime("{$year}-12-31");
        
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.user = :user')
            ->andWhere('pc.tanggalPengajuan BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistikByStatus(): array
    {
        $result = $this->createQueryBuilder('pc')
            ->select('pc.status, COUNT(pc.id) as total')
            ->groupBy('pc.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']] = (int) $row['total'];
        }

        return $statistics;
    }

    public function getStatistikByBulan(int $year): array
    {
        $result = $this->createQueryBuilder('pc')
            ->select('MONTH(pc.tanggalPengajuan) as bulan, COUNT(pc.id) as total')
            ->andWhere('YEAR(pc.tanggalPengajuan) = :year')
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

    public function findOverlappingCuti(User $user, \DateTimeInterface $tanggalMulai, \DateTimeInterface $tanggalSelesai, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->andWhere('pc.user = :user')
            ->andWhere('pc.status IN (:statuses)')
            ->andWhere('pc.tanggalMulai <= :tanggalSelesai')
            ->andWhere('pc.tanggalSelesai >= :tanggalMulai')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['diajukan', 'disetujui'])
            ->setParameter('tanggalMulai', $tanggalMulai)
            ->setParameter('tanggalSelesai', $tanggalSelesai);

        if ($excludeId) {
            $qb->andWhere('pc.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTotalHariCutiByUserAndYear(User $user, int $year): int
    {
        $startDate = new \DateTime("{$year}-01-01");
        $endDate = new \DateTime("{$year}-12-31");
        
        $result = $this->createQueryBuilder('pc')
            ->select('SUM(pc.lamaCuti) as total_hari')
            ->andWhere('pc.user = :user')
            ->andWhere('pc.status = :status')
            ->andWhere('pc.tanggalMulai BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('status', 'disetujui')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUnitKerja($unitKerja): array
    {
        return $this->createQueryBuilder('pc')
            ->join('pc.user', 'u')
            ->andWhere('u.unitKerja = :unitKerja')
            ->setParameter('unitKerja', $unitKerja)
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pengajuan cuti by user with optional status filter
     */
    public function findByUserWithStatus(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->andWhere('pc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pc.tanggalPengajuan', 'DESC');

        if ($status !== null) {
            $qb->andWhere('pc.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find pengajuan cuti for approval by unit
     */
    public function findForApprovalByUnit(UnitKerja $unit, ?string $status = 'diajukan'): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->join('pc.user', 'u')
            ->andWhere('u.unitKerja = :unit')
            ->setParameter('unit', $unit)
            ->orderBy('pc.tanggalPengajuan', 'ASC');

        if ($status !== null) {
            $qb->andWhere('pc.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count pengajuan cuti by jenis per unit for a specific year
     */
    public function countByJenisPerUnit(UnitKerja $unit, int $tahun): array
    {
        $startDate = new \DateTime("{$tahun}-01-01");
        $endDate = new \DateTime("{$tahun}-12-31");

        $result = $this->createQueryBuilder('pc')
            ->select('jc.kode as jenis_kode, jc.nama as jenis_nama, COUNT(pc.id) as total')
            ->join('pc.jenisCuti', 'jc')
            ->join('pc.user', 'u')
            ->andWhere('u.unitKerja = :unit')
            ->andWhere('pc.tanggalMulai BETWEEN :startDate AND :endDate')
            ->andWhere('pc.status = :status')
            ->setParameter('unit', $unit)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'disetujui')
            ->groupBy('jc.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Find pengajuan by status with optional statuses array
     */
    public function findByStatuses(array $statuses): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->join('pc.user', 'u')
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all pending approval requests (for global admin view)
     */
    public function findAllPendingApproval(): array
    {
        return $this->findByStatus('diajukan');
    }

    /**
     * Find all processed requests (disetujui/ditolak)
     */
    public function findAllProcessed(): array
    {
        return $this->findByStatuses(['disetujui', 'ditolak']);
    }

    /**
     * Find processed requests by unit
     */
    public function findProcessedByUnit(UnitKerja $unit): array
    {
        return $this->createQueryBuilder('pc')
            ->join('pc.user', 'u')
            ->andWhere('u.unitKerja = :unit')
            ->andWhere('pc.status IN (:statuses)')
            ->setParameter('unit', $unit)
            ->setParameter('statuses', ['disetujui', 'ditolak'])
            ->orderBy('pc.tanggalPengajuan', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pengajuan cuti by various criteria for reporting
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->join('pc.user', 'u')
            ->join('pc.jenisCuti', 'jc');

        if (isset($criteria['tahun'])) {
            $tahun = (int) $criteria['tahun'];
            $startDate = new \DateTime("{$tahun}-01-01");
            $endDate = new \DateTime("{$tahun}-12-31");
            $qb->andWhere('pc.tanggalMulai BETWEEN :startDate AND :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        if (isset($criteria['bulan'])) {
            $bulan = (int) $criteria['bulan'];
            $qb->andWhere('MONTH(pc.tanggalMulai) = :bulan')
               ->setParameter('bulan', $bulan);
        }

        if (isset($criteria['status'])) {
            $qb->andWhere('pc.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['jenisCuti'])) {
            $qb->andWhere('pc.jenisCuti = :jenisCuti')
               ->setParameter('jenisCuti', $criteria['jenisCuti']);
        }

        if (isset($criteria['user'])) {
            $qb->andWhere('pc.user = :user')
               ->setParameter('user', $criteria['user']);
        }

        return $qb->orderBy('pc.tanggalPengajuan', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}