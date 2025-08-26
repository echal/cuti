<?php

namespace App\Repository;

use App\Entity\HakCuti;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HakCuti>
 */
class HakCutiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HakCuti::class);
    }

    public function save(HakCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HakCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserAndTahun(User $user, int $tahun): ?HakCuti
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.user = :user')
            ->andWhere('hc.tahun = :tahun')
            ->setParameter('user', $user)
            ->setParameter('tahun', $tahun)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('hc.tahun', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTahun(int $tahun): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.tahun = :tahun')
            ->setParameter('tahun', $tahun)
            ->join('hc.user', 'u')
            ->orderBy('u.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentYearByUser(User $user): ?HakCuti
    {
        $currentYear = (int) date('Y');
        return $this->findByUserAndTahun($user, $currentYear);
    }

    public function findWithCarryOver(): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.carryOver = :carryOver')
            ->setParameter('carryOver', true)
            ->join('hc.user', 'u')
            ->orderBy('hc.tahun', 'DESC')
            ->addOrderBy('u.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiringSoon(int $minimumSisa = 1): array
    {
        $currentYear = (int) date('Y');
        
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.tahun = :currentYear')
            ->andWhere('hc.sisa >= :minimumSisa')
            ->setParameter('currentYear', $currentYear)
            ->setParameter('minimumSisa', $minimumSisa)
            ->join('hc.user', 'u')
            ->orderBy('hc.sisa', 'DESC')
            ->addOrderBy('u.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findHabis(): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.sisa <= 0')
            ->join('hc.user', 'u')
            ->orderBy('hc.tahun', 'DESC')
            ->addOrderBy('u.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistikByTahun(int $tahun): array
    {
        $result = $this->createQueryBuilder('hc')
            ->select('
                COUNT(hc.id) as total_records,
                AVG(hc.hakTahunan) as avg_hak_tahunan,
                AVG(hc.terpakai) as avg_terpakai,
                AVG(hc.sisa) as avg_sisa,
                SUM(CASE WHEN hc.sisa <= 0 THEN 1 ELSE 0 END) as total_habis,
                SUM(CASE WHEN hc.carryOver = true THEN 1 ELSE 0 END) as total_cuti_dibawa
            ')
            ->andWhere('hc.tahun = :tahun')
            ->setParameter('tahun', $tahun)
            ->getQuery()
            ->getSingleResult();

        return [
            'total_records' => (int) $result['total_records'],
            'avg_hak_tahunan' => round((float) $result['avg_hak_tahunan'], 2),
            'avg_terpakai' => round((float) $result['avg_terpakai'], 2),
            'avg_sisa' => round((float) $result['avg_sisa'], 2),
            'total_habis' => (int) $result['total_habis'],
            'total_cuti_dibawa' => (int) $result['total_cuti_dibawa']
        ];
    }

    public function createOrUpdateHakCuti(User $user, int $tahun, int $hakTahunan = 12): HakCuti
    {
        $hakCuti = $this->findByUserAndTahun($user, $tahun);
        
        if (!$hakCuti) {
            $hakCuti = new HakCuti();
            $hakCuti->setUser($user);
            $hakCuti->setTahun($tahun);
        }
        
        $hakCuti->setHakTahunan($hakTahunan);
        $this->save($hakCuti, true);
        
        return $hakCuti;
    }

    /**
     * Find one hak cuti by user and tahun (alias for existing method)
     */
    public function findOneByUserAndTahun(User $user, int $tahun): ?HakCuti
    {
        return $this->findByUserAndTahun($user, $tahun);
    }

    /**
     * Get sisa cuti for user in specific year
     */
    public function getSisa(User $user, int $tahun): int
    {
        $hakCuti = $this->findByUserAndTahun($user, $tahun);
        
        return $hakCuti ? $hakCuti->getSisa() : 0;
    }
}