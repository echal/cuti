<?php

namespace App\Repository;

use App\Entity\Pejabat;
use App\Entity\UnitKerja;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pejabat>
 */
class PejabatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pejabat::class);
    }

    public function save(Pejabat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Pejabat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByNip(string $nip): ?Pejabat
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nip = :nip')
            ->setParameter('nip', $nip)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAktif(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'aktif')
            ->orderBy('p.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUnitKerja(int $unitKerjaId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.unitKerja = :unitKerjaId')
            ->setParameter('unitKerjaId', $unitKerjaId)
            ->orderBy('p.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMasihMenjabat(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.selesaiMenjabat IS NULL OR p.selesaiMenjabat >= :today')
            ->setParameter('status', 'aktif')
            ->setParameter('today', new \DateTime())
            ->orderBy('p.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findKakanwil(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.unitKerja IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'aktif')
            ->orderBy('p.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByJabatanContaining(string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.jabatan LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('p.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find kepala unit yang aktif pada tanggal tertentu
     */
    public function findKepalaUnit(UnitKerja $unit, \DateTimeInterface $onDate): ?Pejabat
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.unitKerja = :unit')
            ->andWhere('p.status = :status')
            ->andWhere('p.mulaiMenjabat <= :onDate')
            ->andWhere('p.selesaiMenjabat IS NULL OR p.selesaiMenjabat >= :onDate')
            ->andWhere('p.jabatan LIKE :kepala OR p.jabatan LIKE :kasubag')
            ->setParameter('unit', $unit)
            ->setParameter('status', 'aktif')
            ->setParameter('onDate', $onDate)
            ->setParameter('kepala', '%Kepala%')
            ->setParameter('kasubag', '%Kasubag%')
            ->orderBy('p.mulaiMenjabat', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find Kakanwil yang aktif pada tanggal tertentu
     */
    public function findKakanwilOnDate(\DateTimeInterface $onDate): ?Pejabat
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.unitKerja IS NULL') // Pejabat struktural
            ->andWhere('p.status = :status')
            ->andWhere('p.mulaiMenjabat <= :onDate')
            ->andWhere('p.selesaiMenjabat IS NULL OR p.selesaiMenjabat >= :onDate')
            ->andWhere('p.jabatan LIKE :kakanwil OR p.jabatan LIKE :kepala')
            ->setParameter('status', 'aktif')
            ->setParameter('onDate', $onDate)
            ->setParameter('kakanwil', '%Kakanwil%')
            ->setParameter('kepala', '%Kepala Kantor Wilayah%')
            ->orderBy('p.mulaiMenjabat', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}