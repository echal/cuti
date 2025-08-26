<?php

namespace App\Repository;

use App\Entity\UnitKerja;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UnitKerja>
 */
class UnitKerjaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitKerja::class);
    }

    public function save(UnitKerja $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UnitKerja $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByKode(string $kode): ?UnitKerja
    {
        return $this->createQueryBuilder('uk')
            ->andWhere('uk.kode = :kode')
            ->setParameter('kode', $kode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('uk')
            ->orderBy('uk.kode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNamaContaining(string $searchTerm): array
    {
        return $this->createQueryBuilder('uk')
            ->andWhere('uk.nama LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('uk.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }
}