<?php

namespace App\DataFixtures;

use App\Entity\Konfigurasi;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class KonfigurasiFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Data Kepala Kantor
        $kepalaKantorNama = new Konfigurasi();
        $kepalaKantorNama->setKey('kepala_kantor_nama');
        $kepalaKantorNama->setValue('Dr. H. Abdul Rahman, M.Pd.');
        $kepalaKantorNama->setDescription('Nama Kepala Kantor');
        $manager->persist($kepalaKantorNama);

        $kepalaKantorNip = new Konfigurasi();
        $kepalaKantorNip->setKey('kepala_kantor_nip');
        $kepalaKantorNip->setValue('196812151994031003');
        $kepalaKantorNip->setDescription('NIP Kepala Kantor');
        $manager->persist($kepalaKantorNip);

        $kepalaKantorJabatan = new Konfigurasi();
        $kepalaKantorJabatan->setKey('kepala_kantor_jabatan');
        $kepalaKantorJabatan->setValue('Kepala Kanwil Kemenag Sulbar');
        $kepalaKantorJabatan->setDescription('Jabatan Kepala Kantor');
        $manager->persist($kepalaKantorJabatan);

        $manager->flush();
    }
}