<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\JenisCuti;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class JenisCutiFixtures extends Fixture
{

    /**
     * Load jenis cuti data for PNS/PPPK
     */
    public function load(ObjectManager $manager): void
    {
        $jenisCutiData = [
            [
                'kode' => 'CT',
                'nama' => 'Cuti Tahunan',
                'deskripsi' => 'Cuti yang diberikan kepada PNS/PPPK setiap tahun untuk keperluan istirahat',
                'durasi_max' => 12,
                'dasar_hukum' => 'PP 11/2017',
                'tersedia_untuk' => 'ALL'
            ],
            [
                'kode' => 'CB',
                'nama' => 'Cuti Besar',
                'deskripsi' => 'Cuti yang diberikan kepada PNS yang telah bekerja terus menerus selama 6 tahun',
                'durasi_max' => 90,
                'dasar_hukum' => 'PP 11/2017 Pasal 26',
                'tersedia_untuk' => 'PNS'
            ],
            [
                'kode' => 'CS',
                'nama' => 'Cuti Sakit',
                'deskripsi' => 'Cuti yang diberikan kepada PNS/PPPK yang sakit berdasarkan surat keterangan dokter',
                'durasi_max' => 365,
                'dasar_hukum' => 'PP 11/2017 Pasal 31',
                'tersedia_untuk' => 'ALL'
            ],
            [
                'kode' => 'CM',
                'nama' => 'Cuti Melahirkan',
                'deskripsi' => 'Cuti yang diberikan kepada PNS/PPPK wanita yang akan melahirkan atau keguguran',
                'durasi_max' => 90,
                'dasar_hukum' => 'PP 11/2017 Pasal 33',
                'tersedia_untuk' => 'ALL'
            ],
            [
                'kode' => 'CAP',
                'nama' => 'Cuti Alasan Penting',
                'deskripsi' => 'Cuti yang diberikan kepada PNS/PPPK untuk keperluan yang sifatnya sangat penting',
                'durasi_max' => 30,
                'dasar_hukum' => 'PP 11/2017 Pasal 35',
                'tersedia_untuk' => 'ALL'
            ],
            [
                'kode' => 'CLTN',
                'nama' => 'Cuti di Luar Tanggungan Negara',
                'deskripsi' => 'Cuti yang diberikan kepada PNS dengan tidak memperoleh gaji dan tunjangan',
                'durasi_max' => 1095,
                'dasar_hukum' => 'PP 11/2017 Pasal 37',
                'tersedia_untuk' => 'PNS'
            ]
        ];

        foreach ($jenisCutiData as $data) {
            $jenisCuti = new JenisCuti();
            $jenisCuti->setKode($data['kode']);
            $jenisCuti->setNama($data['nama']);
            $jenisCuti->setDeskripsi($data['deskripsi']);
            $jenisCuti->setDurasiMax($data['durasi_max']);
            $jenisCuti->setDasarHukum($data['dasar_hukum']);
            $jenisCuti->setTersediUntuk($data['tersedia_untuk']);

            $manager->persist($jenisCuti);

            // Add reference untuk digunakan di fixtures lain
            $this->addReference('jenis-cuti-' . strtolower($data['kode']), $jenisCuti);
        }

        $manager->flush();
    }
}