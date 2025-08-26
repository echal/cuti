<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByNip(string $nip): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.nip = :nip')
            ->setParameter('nip', $nip)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUnitKerja(int $unitKerjaId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.unitKerja = :unitKerjaId')
            ->setParameter('unitKerjaId', $unitKerjaId)
            ->orderBy('u.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatusPegawai(string $status): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.statusPegawai = :status')
            ->setParameter('status', $status)
            ->orderBy('u.nama', 'ASC')
            ->getQuery()
            ->getResult();
    }
}