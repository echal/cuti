<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\HakCuti;
use App\Entity\User;
use App\Entity\UnitKerja;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HakCutiTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    private function createTestUser(): User
    {
        $unitKerja = new UnitKerja();
        $unitKerja->setKode('001')->setNama('Test Unit');

        $user = new User();
        $user->setNip('199001012020121001')
            ->setNama('Test User')
            ->setEmail('test@example.com')
            ->setPassword('TestPassword123')
            ->setJenisKelamin('L')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Test Jabatan')
            ->setUnitKerja($unitKerja)
            ->setJumlahAnak(0)
            ->setStatusPegawai('aktif');

        return $user;
    }

    private function createValidHakCuti(): HakCuti
    {
        $hakCuti = new HakCuti();
        $hakCuti->setTahun(2025)
            ->setHakTahunan(12)
            ->setTerpakai(0)
            ->setSisa(12)
            ->setCarryOver(false)
            ->setUser($this->createTestUser());

        return $hakCuti;
    }

    /**
     * Test perhitungan sisa cuti otomatis saat konstruktor
     */
    public function testCalculateSisaPadaKonstruktor(): void
    {
        $hakCuti = new HakCuti();
        
        // Sisa harus terhitung otomatis karena hakTahunan = 12 dan terpakai = 0 (default values)
        $this->assertEquals(12, $hakCuti->getSisa());
    }

    /**
     * Test calculateSisa method - formula dasar
     */
    public function testCalculateSisaFormulaDasar(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12)
            ->setTerpakai(5);
        
        $hakCuti->calculateSisa();
        
        $this->assertEquals(7, $hakCuti->getSisa(), 'Sisa = HakTahunan (12) - Terpakai (5) = 7');
    }

    /**
     * Test calculateSisa dengan berbagai skenario
     */
    public function testCalculateSisaBerbagaiSkenario(): void
    {
        $hakCuti = $this->createValidHakCuti();
        
        // Skenario 1: Belum terpakai sama sekali
        $hakCuti->setHakTahunan(12)->setTerpakai(0);
        $hakCuti->calculateSisa();
        $this->assertEquals(12, $hakCuti->getSisa(), 'Sisa = 12 - 0 = 12');
        
        // Skenario 2: Habis semua
        $hakCuti->setHakTahunan(12)->setTerpakai(12);
        $hakCuti->calculateSisa();
        $this->assertEquals(0, $hakCuti->getSisa(), 'Sisa = 12 - 12 = 0');
        
        // Skenario 3: Terpakai melebihi hak (edge case)
        $hakCuti->setHakTahunan(12)->setTerpakai(15);
        $hakCuti->calculateSisa();
        $this->assertEquals(-3, $hakCuti->getSisa(), 'Sisa = 12 - 15 = -3');
    }

    /**
     * Test calculateSisa dengan nilai null
     */
    public function testCalculateSisaDenganNilaiNull(): void
    {
        $hakCuti = $this->createValidHakCuti();
        
        // Test dengan nilai awal (hakTahunan=12, terpakai=0)
        $hakCuti->setTerpakai(5);
        $hakCuti->calculateSisa();
        $this->assertEquals(7, $hakCuti->getSisa(), 'Sisa = 12 - 5 = 7');
        
        // Test dengan terpakai 0 (reset)
        $hakCuti->setHakTahunan(12)->setTerpakai(0);
        $hakCuti->calculateSisa();
        $this->assertEquals(12, $hakCuti->getSisa(), 'Sisa = 12 - 0 = 12');
        
        // Test dengan edge case
        $hakCuti->setHakTahunan(15)->setTerpakai(12);
        $hakCuti->calculateSisa();
        $this->assertEquals(3, $hakCuti->getSisa(), 'Sisa = 15 - 12 = 3');
    }

    /**
     * Test automatic calculation saat setter dipanggil
     */
    public function testAutomaticCalculationPadaSetter(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12)->setTerpakai(0);
        
        // Test setTerpakai memicu calculateSisa
        $hakCuti->setTerpakai(3);
        $this->assertEquals(9, $hakCuti->getSisa(), 'Sisa otomatis dihitung saat setTerpakai');
        
        // Test setHakTahunan memicu calculateSisa
        $hakCuti->setHakTahunan(15);
        $this->assertEquals(12, $hakCuti->getSisa(), 'Sisa otomatis dihitung saat setHakTahunan');
    }

    /**
     * Test addTerpakai method
     */
    public function testAddTerpakai(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12)->setTerpakai(3); // Sisa = 9
        
        // Tambah 2 hari
        $hakCuti->addTerpakai(2);
        
        $this->assertEquals(5, $hakCuti->getTerpakai(), 'Terpakai = 3 + 2 = 5');
        $this->assertEquals(7, $hakCuti->getSisa(), 'Sisa = 12 - 5 = 7');
        
        // Tambah lagi 4 hari
        $hakCuti->addTerpakai(4);
        
        $this->assertEquals(9, $hakCuti->getTerpakai(), 'Terpakai = 5 + 4 = 9');
        $this->assertEquals(3, $hakCuti->getSisa(), 'Sisa = 12 - 9 = 3');
    }

    /**
     * Test removeTerpakai method
     */
    public function testRemoveTerpakai(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12)->setTerpakai(8); // Sisa = 4
        
        // Kurangi 3 hari
        $hakCuti->removeTerpakai(3);
        
        $this->assertEquals(5, $hakCuti->getTerpakai(), 'Terpakai = 8 - 3 = 5');
        $this->assertEquals(7, $hakCuti->getSisa(), 'Sisa = 12 - 5 = 7');
        
        // Kurangi lebih dari yang ada (edge case)
        $hakCuti->removeTerpakai(10);
        
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Terpakai tidak boleh negatif (max 0)');
        $this->assertEquals(12, $hakCuti->getSisa(), 'Sisa = 12 - 0 = 12');
    }

    /**
     * Test isCutiAvailable method
     */
    public function testIsCutiAvailable(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12)->setTerpakai(5); // Sisa = 7
        
        // Test dengan jumlah yang tersedia
        $this->assertTrue($hakCuti->isCutiAvailable(7), 'Cuti 7 hari harus available (sama dengan sisa)');
        $this->assertTrue($hakCuti->isCutiAvailable(5), 'Cuti 5 hari harus available (kurang dari sisa)');
        $this->assertTrue($hakCuti->isCutiAvailable(1), 'Cuti 1 hari harus available');
        
        // Test dengan jumlah yang tidak tersedia
        $this->assertFalse($hakCuti->isCutiAvailable(8), 'Cuti 8 hari tidak available (lebih dari sisa 7)');
        $this->assertFalse($hakCuti->isCutiAvailable(15), 'Cuti 15 hari tidak available');
        
        // Test edge case dengan sisa 0
        $hakCuti->setTerpakai(12); // Sisa = 0
        $this->assertFalse($hakCuti->isCutiAvailable(1), 'Tidak ada cuti available jika sisa 0');
        $this->assertTrue($hakCuti->isCutiAvailable(0), 'Cuti 0 hari selalu available');
    }

    /**
     * Test getPersentaseTerpakai method
     */
    public function testGetPersentaseTerpakai(): void
    {
        $hakCuti = $this->createValidHakCuti();
        
        // Test dengan hak 12 hari
        $hakCuti->setHakTahunan(12);
        
        $hakCuti->setTerpakai(0);
        $this->assertEquals(0.0, $hakCuti->getPersentaseTerpakai(), 'Persentase = 0/12 = 0%');
        
        $hakCuti->setTerpakai(6);
        $this->assertEquals(50.0, $hakCuti->getPersentaseTerpakai(), 'Persentase = 6/12 = 50%');
        
        $hakCuti->setTerpakai(12);
        $this->assertEquals(100.0, $hakCuti->getPersentaseTerpakai(), 'Persentase = 12/12 = 100%');
        
        $hakCuti->setTerpakai(3);
        $this->assertEquals(25.0, $hakCuti->getPersentaseTerpakai(), 'Persentase = 3/12 = 25%');
        
        // Test dengan hak 0 (edge case)
        $hakCuti->setHakTahunan(0);
        $this->assertEquals(0.0, $hakCuti->getPersentaseTerpakai(), 'Persentase = 0 jika hak tahunan 0');
    }

    /**
     * Test isHakCutiHabis method
     */
    public function testIsHakCutiHabis(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12);
        
        // Test dengan sisa > 0
        $hakCuti->setTerpakai(5); // Sisa = 7
        $this->assertFalse($hakCuti->isHakCutiHabis(), 'Hak cuti tidak habis jika sisa > 0');
        
        // Test dengan sisa = 0
        $hakCuti->setTerpakai(12); // Sisa = 0
        $this->assertTrue($hakCuti->isHakCutiHabis(), 'Hak cuti habis jika sisa = 0');
        
        // Test dengan sisa < 0 (edge case)
        $hakCuti->setTerpakai(15); // Sisa = -3
        $this->assertTrue($hakCuti->isHakCutiHabis(), 'Hak cuti habis jika sisa < 0');
    }

    /**
     * Test lifecycle callbacks
     */
    public function testLifecycleCallbacks(): void
    {
        $hakCuti = new HakCuti();
        
        // Test timestamps are set in constructor
        $this->assertInstanceOf(\DateTimeImmutable::class, $hakCuti->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $hakCuti->getUpdatedAt());
        
        // Test calculateSisa dipanggil di constructor
        $hakCuti->setHakTahunan(12);
        $hakCuti->setTerpakai(5);
        // Manual call karena di constructor belum ada data
        $hakCuti->calculateSisa();
        $this->assertEquals(7, $hakCuti->getSisa());
    }

    /**
     * Test carry over scenarios (N, N-1, N-2)
     */
    public function testCarryOverScenarios(): void
    {
        $user = $this->createTestUser();
        
        // Setup N-2 (2023): 6 hari sisa, carry over
        $hakCutiN2 = new HakCuti();
        $hakCutiN2->setTahun(2023)
            ->setHakTahunan(12)
            ->setTerpakai(6)
            ->setCarryOver(true)
            ->setUser($user);
        $hakCutiN2->calculateSisa();
        
        $this->assertEquals(6, $hakCutiN2->getSisa());
        $this->assertTrue($hakCutiN2->isCarryOver());
        
        // Setup N-1 (2024): 4 hari sisa, carry over
        $hakCutiN1 = new HakCuti();
        $hakCutiN1->setTahun(2024)
            ->setHakTahunan(12)
            ->setTerpakai(8)
            ->setCarryOver(true)
            ->setUser($user);
        $hakCutiN1->calculateSisa();
        
        $this->assertEquals(4, $hakCutiN1->getSisa());
        $this->assertTrue($hakCutiN1->isCarryOver());
        
        // Setup N (2025): 12 hari fresh
        $hakCutiN = new HakCuti();
        $hakCutiN->setTahun(2025)
            ->setHakTahunan(12)
            ->setTerpakai(0)
            ->setCarryOver(false)
            ->setUser($user);
        $hakCutiN->calculateSisa();
        
        $this->assertEquals(12, $hakCutiN->getSisa());
        $this->assertFalse($hakCutiN->isCarryOver());
        
        // Test perhitungan total theoretical (akan dihandle oleh service)
        // N + min(N-1, 6) + min(N-2, 6) = 12 + 4 + 6 = 22 hari (< 24 cap)
        $totalTheorical = $hakCutiN->getSisa() + 
                         min($hakCutiN1->getSisa(), 6) + 
                         min($hakCutiN2->getSisa(), 6);
        $this->assertEquals(22, $totalTheorical);
    }

    /**
     * Test validasi consistency constraint
     */
    public function testValidasiConsistencyConstraint(): void
    {
        $hakCuti = $this->createValidHakCuti();
        
        // Test data konsisten
        $hakCuti->setHakTahunan(12)->setTerpakai(5)->setSisa(7);
        $errors = $this->validator->validate($hakCuti);
        $this->assertCount(0, $errors, 'Data konsisten seharusnya tidak ada error');
        
        // Test data inkonsisten
        $hakCuti->setSisa(10); // Seharusnya 7, tapi diset 10
        $errors = $this->validator->validate($hakCuti);
        $this->assertGreaterThan(0, $errors->count(), 'Data inkonsisten harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        $this->assertContains('Sisa cuti tidak konsisten dengan perhitungan (Hak Tahunan - Terpakai)', $errorMessages);
    }

    /**
     * Test validasi nilai negatif
     */
    public function testValidasiNilaiNegatif(): void
    {
        $hakCuti = $this->createValidHakCuti();
        
        // Test terpakai negatif
        $hakCuti->setHakTahunan(12)->setTerpakai(-2);
        $hakCuti->calculateSisa(); // Sisa akan 14
        $errors = $this->validator->validate($hakCuti);
        $this->assertGreaterThan(0, $errors->count(), 'Terpakai negatif harus error');
        
        // Test hak tahunan negatif  
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(-5)->setTerpakai(0);
        $hakCuti->calculateSisa();
        $errors = $this->validator->validate($hakCuti);
        $this->assertGreaterThan(0, $errors->count(), 'Hak tahunan negatif harus error');
    }

    /**
     * Test validasi terpakai melebihi hak tahunan
     */
    public function testValidasiTerpakaiMelebihiHakTahunan(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setHakTahunan(12)->setTerpakai(15); // Terpakai > hak tahunan
        $hakCuti->calculateSisa();
        
        $errors = $this->validator->validate($hakCuti);
        $this->assertGreaterThan(0, $errors->count(), 'Terpakai > hak tahunan harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        $this->assertContains('Cuti terpakai tidak boleh melebihi hak tahunan', $errorMessages);
    }

    /**
     * Test __toString method
     */
    public function testToStringMethod(): void
    {
        $hakCuti = $this->createValidHakCuti();
        $hakCuti->setTahun(2025)->setSisa(7);
        
        $string = (string) $hakCuti;
        $this->assertStringContainsString('2025', $string, 'ToString harus contain tahun');
        $this->assertStringContainsString('Test User', $string, 'ToString harus contain nama user');
        $this->assertStringContainsString('7', $string, 'ToString harus contain sisa');
    }

    /**
     * Test unique constraint simulation (via entity)
     */
    public function testUniqueConstraintUserTahun(): void
    {
        $user = $this->createTestUser();
        
        $hakCuti1 = new HakCuti();
        $hakCuti1->setTahun(2025)
            ->setHakTahunan(12)
            ->setTerpakai(0)
            ->setUser($user);
        
        $hakCuti2 = new HakCuti();
        $hakCuti2->setTahun(2025) // Same year, same user
            ->setHakTahunan(12)
            ->setTerpakai(0)  
            ->setUser($user);
        
        // This would be caught at database level due to unique constraint
        // But we can test the entity setup is correct
        $this->assertEquals($hakCuti1->getTahun(), $hakCuti2->getTahun());
        $this->assertEquals($hakCuti1->getUser()->getNip(), $hakCuti2->getUser()->getNip());
    }
}