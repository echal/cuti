<?php

namespace App\Repository;

use App\Entity\Konfigurasi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Konfigurasi>
 */
class KonfigurasiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Konfigurasi::class);
    }

    public function save(Konfigurasi $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Konfigurasi $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find configuration by key
     */
    public function findByKey(string $key): ?Konfigurasi
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.key = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get configuration value by key
     */
    public function getValue(string $key): ?string
    {
        $config = $this->findByKey($key);
        return $config ? $config->getValue() : null;
    }

    /**
     * Set configuration value
     */
    public function setValue(string $key, string $value, ?string $description = null): void
    {
        $config = $this->findByKey($key);
        
        if (!$config) {
            $config = new Konfigurasi();
            $config->setKey($key);
            $config->setDescription($description);
        }
        
        $config->setValue($value);
        
        $this->save($config, true);
    }

    /**
     * Get Kepala Kantor configuration
     */
    public function getKepalaKantor(): array
    {
        return [
            'nama' => $this->getValue('kepala_kantor_nama') ?: 'Kepala Kantor',
            'nip' => $this->getValue('kepala_kantor_nip') ?: '-',
            'jabatan' => $this->getValue('kepala_kantor_jabatan') ?: 'Kepala Kanwil Kemenag Sulbar'
        ];
    }
}