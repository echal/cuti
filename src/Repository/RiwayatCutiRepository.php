<?php

namespace App\Repository;

use App\Entity\RiwayatCuti;
use App\Entity\PengajuanCuti;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RiwayatCuti>
 */
class RiwayatCutiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiwayatCuti::class);
    }

    public function save(RiwayatCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RiwayatCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByPengajuanCuti(PengajuanCuti $pengajuanCuti): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.pengajuanCuti = :pengajuanCuti')
            ->setParameter('pengajuanCuti', $pengajuanCuti)
            ->orderBy('rc.tanggalAksi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAksi(string $aksi): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.aksi = :aksi')
            ->setParameter('aksi', $aksi)
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentActivity(int $limit = 20): array
    {
        return $this->createQueryBuilder('rc')
            ->join('rc.user', 'u')
            ->join('rc.pengajuanCuti', 'pc')
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.tanggalAksi BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->join('rc.user', 'u')
            ->join('rc.pengajuanCuti', 'pc')
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findApprovalsByUser(User $user): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.user = :user')
            ->andWhere('rc.aksi IN (:actions)')
            ->setParameter('user', $user)
            ->setParameter('actions', ['disetujui', 'ditolak'])
            ->join('rc.pengajuanCuti', 'pc')
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistikAksi(): array
    {
        $result = $this->createQueryBuilder('rc')
            ->select('rc.aksi, COUNT(rc.id) as total')
            ->groupBy('rc.aksi')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['aksi']] = (int) $row['total'];
        }

        return $statistics;
    }

    public function getActivityByMonth(int $year): array
    {
        $result = $this->createQueryBuilder('rc')
            ->select('MONTH(rc.tanggalAksi) as bulan, COUNT(rc.id) as total')
            ->andWhere('YEAR(rc.tanggalAksi) = :year')
            ->setParameter('year', $year)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->getQuery()
            ->getResult();

        $activity = array_fill(1, 12, 0);
        foreach ($result as $row) {
            $activity[(int) $row['bulan']] = (int) $row['total'];
        }

        return $activity;
    }

    public function findWithCatatan(): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.catatan IS NOT NULL')
            ->andWhere('rc.catatan != :empty')
            ->setParameter('empty', '')
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndAksi(User $user, string $aksi): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.user = :user')
            ->andWhere('rc.aksi = :aksi')
            ->setParameter('user', $user)
            ->setParameter('aksi', $aksi)
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getLastActionByPengajuanCuti(PengajuanCuti $pengajuanCuti): ?RiwayatCuti
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.pengajuanCuti = :pengajuanCuti')
            ->setParameter('pengajuanCuti', $pengajuanCuti)
            ->orderBy('rc.tanggalAksi', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActionsByUser(User $user, ?string $aksi = null): int
    {
        $qb = $this->createQueryBuilder('rc')
            ->select('COUNT(rc.id)')
            ->andWhere('rc.user = :user')
            ->setParameter('user', $user);

        if ($aksi) {
            $qb->andWhere('rc.aksi = :aksi')
               ->setParameter('aksi', $aksi);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findPendingForReview(User $reviewer): array
    {
        return $this->createQueryBuilder('rc')
            ->join('rc.pengajuanCuti', 'pc')
            ->andWhere('pc.status = :status')
            ->andWhere('pc.pejabatPenyetuju = :reviewer OR pc.pejabatAtasan = :reviewer')
            ->setParameter('status', 'diajukan')
            ->setParameter('reviewer', $reviewer)
            ->orderBy('rc.tanggalAksi', 'ASC')
            ->getQuery()
            ->getResult();
    }
}