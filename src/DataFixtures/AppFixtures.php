<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Pejabat;
use App\Entity\HakCuti;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Load additional data (Pejabat dan HakCuti)
     */
    public function load(ObjectManager $manager): void
    {
        $currentYear = (int) date('Y');

        // Create HakCuti untuk existing users
        $users = [
            $this->getReference('user-199001012020121001', \App\Entity\User::class), // Admin
            $this->getReference('user-198901012014121001', \App\Entity\User::class), // Approver
            $this->getReference('user-199205102022021001', \App\Entity\User::class), // User
        ];

        foreach ($users as $user) {
            // Hak cuti tahun ini
            $hakCuti = new HakCuti();
            $hakCuti->setUser($user)
                   ->setTahun($currentYear)
                   ->setHakTahunan(12)
                   ->setTerpakai(rand(0, 4)) // Random usage
                   ->setCarryOver(false);
            
            $manager->persist($hakCuti);

            // Hak cuti tahun lalu dengan carry over (untuk PNS)
            if ($user->getStatusKepegawaian() === 'PNS') {
                $hakCutiPrev = new HakCuti();
                $hakCutiPrev->setUser($user)
                          ->setTahun($currentYear - 1)
                          ->setHakTahunan(12)
                          ->setTerpakai(rand(8, 10))
                          ->setCarryOver(true);
                
                $manager->persist($hakCutiPrev);
            }
        }

        // Create Pejabat
        $pejabatData = [
            [
                'nama' => 'Dr. H. Ahmad Syukri, M.Ag',
                'nip' => '197505152005011003',
                'jabatan' => 'Kepala Kantor Wilayah Kementerian Agama Sulawesi Barat',
                'unitKerja' => null,
            ],
            [
                'nama' => 'Drs. Muhammad Rizki, M.M',
                'nip' => '198203102009011002',
                'jabatan' => 'Kepala Bagian Tata Usaha',
                'unitKerja' => 'unit-kerja-tu',
            ],
        ];

        foreach ($pejabatData as $data) {
            $pejabat = new Pejabat();
            $pejabat->setNama($data['nama'])
                   ->setNip($data['nip'])
                   ->setJabatan($data['jabatan'])
                   ->setStatus('aktif')
                   ->setMulaiMenjabat(new \DateTime('2023-01-01'));

            if ($data['unitKerja']) {
                $unitKerja = $this->getReference($data['unitKerja'], \App\Entity\UnitKerja::class);
                $pejabat->setUnitKerja($unitKerja);
            }

            $manager->persist($pejabat);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UnitKerjaFixtures::class,
            UserFixtures::class,
            JenisCutiFixtures::class,
        ];
    }
}