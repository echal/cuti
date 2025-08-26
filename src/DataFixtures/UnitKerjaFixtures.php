<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UnitKerja;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UnitKerjaFixtures extends Fixture
{

    /**
     * Load unit kerja data for Kanwil Kemenag Sulbar
     */
    public function load(ObjectManager $manager): void
    {
        $unitsData = [
            ['kode' => 'TU', 'nama' => 'Bagian Tata Usaha'],
            ['kode' => 'BIMAS', 'nama' => 'Bimas Islam'],
            ['kode' => 'MAD', 'nama' => 'Madrasah'],
            ['kode' => 'PAPKIS', 'nama' => 'PAPKIS'],
            ['kode' => 'PHU', 'nama' => 'PHU'],
            ['kode' => 'KRISTEN', 'nama' => 'Kristen'],
            ['kode' => 'KATOLIK', 'nama' => 'Katolik'],
            ['kode' => 'HINDU', 'nama' => 'Hindu'],
            ['kode' => 'BUDDHA', 'nama' => 'Buddha'],
        ];

        foreach ($unitsData as $unitData) {
            $unitKerja = new UnitKerja();
            $unitKerja->setKode($unitData['kode'])
                     ->setNama($unitData['nama']);

            $manager->persist($unitKerja);

            // Add reference untuk digunakan di fixtures lain
            $this->addReference('unit-kerja-' . strtolower($unitData['kode']), $unitKerja);
        }

        $manager->flush();
    }
}