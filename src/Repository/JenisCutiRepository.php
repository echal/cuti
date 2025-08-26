<?php

namespace App\Repository;

use App\Entity\JenisCuti;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JenisCuti>
 */
class JenisCutiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JenisCuti::class);
    }

    public function save(JenisCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JenisCuti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByKode(string $kode): ?JenisCuti
    {
        return $this->createQueryBuilder('jc')
            ->andWhere('jc.kode = :kode')
            ->setParameter('kode', $kode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('jc')
            ->orderBy('jc.kode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTersediUntuk(string $statusKepegawaian): array
    {
        return $this->createQueryBuilder('jc')
            ->andWhere('jc.tersediUntuk = :status OR jc.tersediUntuk = :all')
            ->setParameter('status', $statusKepegawaian)
            ->setParameter('all', 'ALL')
            ->orderBy('jc.kode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForPNS(): array
    {
        return $this->findByTersediUntuk('PNS');
    }

    public function findForPPPK(): array
    {
        return $this->findByTersediUntuk('PPPK');
    }

    public function findWithDurasiMax(): array
    {
        return $this->createQueryBuilder('jc')
            ->andWhere('jc.durasiMax IS NOT NULL')
            ->orderBy('jc.kode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNamaContaining(string $searchTerm): array
    {
        return $this->createQueryBuilder('jc')
            ->andWhere('jc.nama LIKE :searchTerm OR jc.kode LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('jc.kode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get available jenis cuti options as array for forms
     */
    public function getKodeNamaArray(): array
    {
        $jenisCutis = $this->findAllOrdered();
        $options = [];
        
        foreach ($jenisCutis as $jenisCuti) {
            $options[$jenisCuti->getKode()] = sprintf('%s - %s', $jenisCuti->getKode(), $jenisCuti->getNama());
        }
        
        return $options;
    }
}