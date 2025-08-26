<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\HakCuti;
use App\Service\CutiCalculator;
use App\Repository\HakCutiRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CutiCalculatorTest extends TestCase
{
    private CutiCalculator $cutiCalculator;
    private MockObject $hakCutiRepository;
    private MockObject $entityManager;

    protected function setUp(): void
    {
        $this->hakCutiRepository = $this->createMock(HakCutiRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->cutiCalculator = new CutiCalculator(
            $this->hakCutiRepository,
            $this->entityManager
        );
    }

    public function testHitungHakTahunAn_DefaultValue(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PPPK');
        
        $result = $this->cutiCalculator->hitungHakTahunan($user, 2024);
        
        $this->assertEquals(12, $result);
    }

    public function testHitungHakTahunAn_PNSWithLongService(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setTmtCpns(new \DateTime('2000-01-01')); // 24 tahun masa kerja di 2024
        
        $result = $this->cutiCalculator->hitungHakTahunan($user, 2024);
        
        $this->assertEquals(15, $result); // Tambahan 3 hari untuk masa kerja >= 20 tahun
    }

    public function testHitungHakTahunAn_PNSWithMediumService(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setTmtCpns(new \DateTime('2010-01-01')); // 14 tahun masa kerja di 2024
        
        $result = $this->cutiCalculator->hitungHakTahunan($user, 2024);
        
        $this->assertEquals(14, $result); // Tambahan 2 hari untuk masa kerja >= 10 tahun
    }

    public function testGetCutiDibawa_WithCarryOverData(): void
    {
        $user = new User();
        
        // Mock hak cuti N-1 dengan carry over
        $hakCutiN1 = new HakCuti();
        $hakCutiN1->setHakTahunan(12);
        $hakCutiN1->setTerpakai(4);
        $hakCutiN1->setCarryOver(true);
        
        // Mock hak cuti N-2 dengan carry over
        $hakCutiN2 = new HakCuti();
        $hakCutiN2->setHakTahunan(12);
        $hakCutiN2->setTerpakai(6);
        $hakCutiN2->setCarryOver(true);
        
        $this->hakCutiRepository
            ->method('findByUserAndTahun')
            ->willReturnMap([
                [$user, 2023, $hakCutiN1], // N-1
                [$user, 2022, $hakCutiN2], // N-2
            ]);
        
        $result = $this->cutiCalculator->getCutiDibawa($user, 2024);
        
        $this->assertEquals(['n1' => 6, 'n2' => 6], $result); // Max 6 hari per tahun
    }

    public function testGetCutiDibawa_WithExcessSisa(): void
    {
        $user = new User();
        
        // Mock hak cuti N-1 dengan sisa > 6
        $hakCutiN1 = new HakCuti();
        $hakCutiN1->setHakTahunan(12);
        $hakCutiN1->setTerpakai(2); // Sisa = 10
        $hakCutiN1->setCarryOver(true);
        
        $this->hakCutiRepository
            ->method('findByUserAndTahun')
            ->willReturnMap([
                [$user, 2023, $hakCutiN1], // N-1
                [$user, 2022, null], // N-2
            ]);
        
        $result = $this->cutiCalculator->getCutiDibawa($user, 2024);
        
        $this->assertEquals(['n1' => 6, 'n2' => 0], $result); // Cap di 6 hari
    }

    public function testHitungTotalHakTahunBerjalan_WithCap(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setTmtCpns(new \DateTime('2010-01-01'));
        
        // Mock carry over yang besar
        $hakCutiN1 = new HakCuti();
        $hakCutiN1->setHakTahunan(12);
        $hakCutiN1->setTerpakai(0); // Sisa = 12, tapi cap di 6
        $hakCutiN1->setCarryOver(true);
        
        $hakCutiN2 = new HakCuti();
        $hakCutiN2->setHakTahunan(12);
        $hakCutiN2->setTerpakai(0); // Sisa = 12, tapi cap di 6
        $hakCutiN2->setCarryOver(true);
        
        $this->hakCutiRepository
            ->method('findByUserAndTahun')
            ->willReturnMap([
                [$user, 2023, $hakCutiN1],
                [$user, 2022, $hakCutiN2],
            ]);
        
        $result = $this->cutiCalculator->hitungTotalHakTahunBerjalan($user, 2024);
        
        // 14 (hak tahun ini) + 6 (carry N-1) + 6 (carry N-2) = 26, tapi cap di 24
        $this->assertEquals(24, $result);
    }

    public function testHitungLamaCuti_CalendarMode(): void
    {
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-01-17');
        
        $result = $this->cutiCalculator->hitungLamaCuti($mulai, $selesai, 'calendar');
        
        $this->assertEquals(3, $result); // 15, 16, 17 = 3 hari
    }

    public function testHitungLamaCuti_WorkdayMode(): void
    {
        // Monday to Wednesday (all workdays)
        $mulai = new \DateTime('2024-01-15'); // Monday
        $selesai = new \DateTime('2024-01-17'); // Wednesday
        
        $result = $this->cutiCalculator->hitungLamaCuti($mulai, $selesai, 'workday');
        
        $this->assertEquals(3, $result); // Monday, Tuesday, Wednesday
    }

    public function testHitungLamaCuti_WorkdayModeWithWeekend(): void
    {
        // Friday to Monday (includes weekend)
        $mulai = new \DateTime('2024-01-19'); // Friday
        $selesai = new \DateTime('2024-01-22'); // Monday
        
        $result = $this->cutiCalculator->hitungLamaCuti($mulai, $selesai, 'workday');
        
        $this->assertEquals(2, $result); // Friday and Monday only (skip weekend)
    }

    public function testHitungLamaCuti_InvalidDateRange(): void
    {
        $mulai = new \DateTime('2024-01-17');
        $selesai = new \DateTime('2024-01-15'); // Earlier than start
        
        $result = $this->cutiCalculator->hitungLamaCuti($mulai, $selesai);
        
        $this->assertEquals(0, $result);
    }

    public function testHitungMasaKerja(): void
    {
        $user = new User();
        $user->setTmtCpns(new \DateTime('2020-01-01'));
        
        $result = $this->cutiCalculator->hitungMasaKerja($user, 2024);
        
        $this->assertEquals(4, $result);
    }

    public function testHitungMasaKerja_NoTmtCpns(): void
    {
        $user = new User();
        // No TMT CPNS set
        
        $result = $this->cutiCalculator->hitungMasaKerja($user, 2024);
        
        $this->assertEquals(0, $result);
    }

    public function testIsEligibleCarryOver_PNSWithSufficientService(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setTmtCpns(new \DateTime('2020-01-01'));
        
        $result = $this->cutiCalculator->isEligibleCarryOver($user, 2024);
        
        $this->assertTrue($result);
    }

    public function testIsEligibleCarryOver_PPPK(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PPPK');
        $user->setTmtCpns(new \DateTime('2020-01-01'));
        
        $result = $this->cutiCalculator->isEligibleCarryOver($user, 2024);
        
        $this->assertFalse($result); // PPPK tidak bisa carry over
    }

    public function testIsEligibleCarryOver_InsufficientService(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setTmtCpns(new \DateTime('2024-01-01')); // Baru 0 tahun
        
        $result = $this->cutiCalculator->isEligibleCarryOver($user, 2024);
        
        $this->assertFalse($result); // Masa kerja < 1 tahun
    }

    /**
     * Test skenario N, N-1, N-2: total capped 24 hari (sesuai permintaan user)
     */
    public function testSkenarioN_N1_N2_TotalCapped24(): void
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setTmtCpns(new \DateTime('2020-01-01'));
        
        // Setup N-2 (2022): 6 hari carry over
        $hakCutiN2 = new HakCuti();
        $hakCutiN2->setTahun(2022)
            ->setHakTahunan(12)
            ->setTerpakai(6)
            ->setCarryOver(true);
        
        // Setup N-1 (2023): 6 hari carry over
        $hakCutiN1 = new HakCuti();
        $hakCutiN1->setTahun(2023)
            ->setHakTahunan(12)
            ->setTerpakai(6)
            ->setCarryOver(true);
        
        $this->hakCutiRepository
            ->method('findByUserAndTahun')
            ->willReturnMap([
                [$user, 2023, $hakCutiN1], // N-1
                [$user, 2022, $hakCutiN2], // N-2
            ]);
        
        $total = $this->cutiCalculator->hitungTotalHakTahunBerjalan($user, 2024);
        
        // N (12) + N-1 (6) + N-2 (6) = 24 hari (sudah di cap)
        $this->assertEquals(24, $total);
    }

    /**
     * Test pengurangan saldo setelah approve (sesuai permintaan user)
     */
    public function testPenguranganSaldoSetelahApprove(): void
    {
        $user = new User();
        $tahun = 2024;
        $jumlahCuti = 10; // Ambil 10 hari cuti
        
        // Setup hak cuti N-2 dengan sisa 4 hari
        $hakCutiN2 = new HakCuti();
        $hakCutiN2->setTahun(2022)
            ->setHakTahunan(12)
            ->setTerpakai(8) // Terpakai 8, sisa 4
            ->setCarryOver(true);
        
        // Setup hak cuti N-1 dengan sisa 8 hari
        $hakCutiN1 = new HakCuti();
        $hakCutiN1->setTahun(2023)
            ->setHakTahunan(12)
            ->setTerpakai(4) // Terpakai 4, sisa 8
            ->setCarryOver(true);
        
        // Setup hak cuti N dengan sisa 12 hari
        $hakCutiN = new HakCuti();
        $hakCutiN->setTahun(2024)
            ->setHakTahunan(12)
            ->setTerpakai(0) // Belum terpakai, sisa 12
            ->setCarryOver(false);
        
        $this->hakCutiRepository
            ->method('findByUserAndTahun')
            ->willReturnMap([
                [$user, 2022, $hakCutiN2], // N-2
                [$user, 2023, $hakCutiN1], // N-1  
                [$user, 2024, $hakCutiN], // N
            ]);
        
        // Expect persist untuk setiap entity yang diupdate
        $this->entityManager
            ->expects($this->exactly(2)) // Hanya N-2 dan N-1 yang diupdate
            ->method('persist');
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        
        // Jalankan pengurangan saldo menggunakan FIFO
        $this->cutiCalculator->kurangiSaldoTahunan($user, $tahun, $jumlahCuti);
        
        // Verifikasi pengurangan FIFO:
        // 1. N-2: 4 hari habis (8 + 4 = 12 terpakai)
        $this->assertEquals(12, $hakCutiN2->getTerpakai());
        $this->assertEquals(0, $hakCutiN2->getSisa());
        
        // 2. N-1: 6 hari habis dari 8 sisa (4 + 6 = 10 terpakai)  
        $this->assertEquals(10, $hakCutiN1->getTerpakai());
        $this->assertEquals(2, $hakCutiN1->getSisa());
        
        // 3. N: tidak tersentuh karena sudah cukup dari N-2 dan N-1
        $this->assertEquals(0, $hakCutiN->getTerpakai());
        $this->assertEquals(12, $hakCutiN->getSisa());
    }
}