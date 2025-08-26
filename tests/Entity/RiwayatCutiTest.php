<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RiwayatCuti;
use App\Entity\PengajuanCuti;
use App\Entity\User;
use App\Entity\JenisCuti;
use App\Entity\UnitKerja;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RiwayatCutiTest extends KernelTestCase
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

    private function createTestPengajuanCuti(): PengajuanCuti
    {
        $jenisCuti = new JenisCuti();
        $jenisCuti->setKode('CT')->setNama('Cuti Tahunan')->setTersediUntuk('ALL');

        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+5 days'))
            ->setLamaCuti(5)
            ->setAlasan('Test pengajuan')
            ->setStatus('draft')
            ->setUser($this->createTestUser())
            ->setJenisCuti($jenisCuti);

        return $pengajuan;
    }

    /**
     * Test constructor sets default timestamps
     */
    public function testConstructorSetsDefaultTimestamps(): void
    {
        $riwayat = new RiwayatCuti();

        $this->assertInstanceOf(\DateTimeImmutable::class, $riwayat->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $riwayat->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $riwayat->getTanggalAksi());

        // All timestamps should be close to now
        $now = new \DateTimeImmutable();
        $diff1 = $now->diff($riwayat->getCreatedAt());
        $diff2 = $now->diff($riwayat->getUpdatedAt());
        $diff3 = $now->diff($riwayat->getTanggalAksi());

        $this->assertLessThan(5, $diff1->s, 'CreatedAt should be close to now');
        $this->assertLessThan(5, $diff2->s, 'UpdatedAt should be close to now');
        $this->assertLessThan(5, $diff3->s, 'TanggalAksi should be close to now');
    }

    /**
     * Test createRiwayat static factory method
     */
    public function testCreateRiwayatStaticFactory(): void
    {
        $pengajuan = $this->createTestPengajuanCuti();
        $user = $this->createTestUser();

        $riwayat = RiwayatCuti::createRiwayat($pengajuan, $user, 'diajukan', 'Test catatan');

        $this->assertEquals($pengajuan, $riwayat->getPengajuanCuti());
        $this->assertEquals($user, $riwayat->getUser());
        $this->assertEquals('diajukan', $riwayat->getAksi());
        $this->assertEquals('Test catatan', $riwayat->getCatatan());
        $this->assertInstanceOf(\DateTimeImmutable::class, $riwayat->getTanggalAksi());
    }

    /**
     * Test createRiwayat without catatan
     */
    public function testCreateRiwayatWithoutCatatan(): void
    {
        $pengajuan = $this->createTestPengajuanCuti();
        $user = $this->createTestUser();

        $riwayat = RiwayatCuti::createRiwayat($pengajuan, $user, 'disetujui');

        $this->assertEquals('disetujui', $riwayat->getAksi());
        $this->assertNull($riwayat->getCatatan());
    }

    /**
     * Test status check methods - isDiajukan
     */
    public function testStatusCheckMethodsDiajukan(): void
    {
        $riwayat = new RiwayatCuti();
        $riwayat->setAksi('diajukan');

        $this->assertTrue($riwayat->isDiajukan());
        $this->assertFalse($riwayat->isDisetujui());
        $this->assertFalse($riwayat->isDitolak());
        $this->assertFalse($riwayat->isDibatalkan());
    }

    /**
     * Test status check methods - isDisetujui
     */
    public function testStatusCheckMethodsDisetujui(): void
    {
        $riwayat = new RiwayatCuti();
        $riwayat->setAksi('disetujui');

        $this->assertFalse($riwayat->isDiajukan());
        $this->assertTrue($riwayat->isDisetujui());
        $this->assertFalse($riwayat->isDitolak());
        $this->assertFalse($riwayat->isDibatalkan());
    }

    /**
     * Test status check methods - isDitolak
     */
    public function testStatusCheckMethodsDitolak(): void
    {
        $riwayat = new RiwayatCuti();
        $riwayat->setAksi('ditolak');

        $this->assertFalse($riwayat->isDiajukan());
        $this->assertFalse($riwayat->isDisetujui());
        $this->assertTrue($riwayat->isDitolak());
        $this->assertFalse($riwayat->isDibatalkan());
    }

    /**
     * Test status check methods - isDibatalkan
     */
    public function testStatusCheckMethodsDibatalkan(): void
    {
        $riwayat = new RiwayatCuti();
        $riwayat->setAksi('dibatalkan');

        $this->assertFalse($riwayat->isDiajukan());
        $this->assertFalse($riwayat->isDisetujui());
        $this->assertFalse($riwayat->isDitolak());
        $this->assertTrue($riwayat->isDibatalkan());
    }

    /**
     * Test getAksiLabel method for all actions
     */
    public function testGetAksiLabel(): void
    {
        $riwayat = new RiwayatCuti();

        $testCases = [
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            'custom_action' => 'Custom_action' // Default case
        ];

        foreach ($testCases as $aksi => $expectedLabel) {
            $riwayat->setAksi($aksi);
            $this->assertEquals($expectedLabel, $riwayat->getAksiLabel(), "Label for '$aksi' should be '$expectedLabel'");
        }
    }

    /**
     * Test getAksiBadgeClass method for all actions
     */
    public function testGetAksiBadgeClass(): void
    {
        $riwayat = new RiwayatCuti();

        $testCases = [
            'diajukan' => 'badge-primary',
            'disetujui' => 'badge-success',
            'ditolak' => 'badge-danger',
            'dibatalkan' => 'badge-warning',
            'unknown_action' => 'badge-secondary' // Default case
        ];

        foreach ($testCases as $aksi => $expectedClass) {
            $riwayat->setAksi($aksi);
            $this->assertEquals($expectedClass, $riwayat->getAksiBadgeClass(), "Badge class for '$aksi' should be '$expectedClass'");
        }
    }

    /**
     * Test hasCatatan method
     */
    public function testHasCatatan(): void
    {
        $riwayat = new RiwayatCuti();

        // Test without catatan
        $this->assertFalse($riwayat->hasCatatan());

        // Test with empty catatan
        $riwayat->setCatatan('');
        $this->assertFalse($riwayat->hasCatatan());

        // Test with null catatan
        $riwayat->setCatatan(null);
        $this->assertFalse($riwayat->hasCatatan());

        // Test with catatan
        $riwayat->setCatatan('Ada catatan');
        $this->assertTrue($riwayat->hasCatatan());

        // Test with whitespace only catatan
        $riwayat->setCatatan('   ');
        $this->assertFalse($riwayat->hasCatatan()); // trim() makes it empty
    }

    /**
     * Test isSystemAction method
     */
    public function testIsSystemAction(): void
    {
        $riwayat = new RiwayatCuti();

        // 'diajukan' is considered system action
        $riwayat->setAksi('diajukan');
        $this->assertTrue($riwayat->isSystemAction());
        $this->assertFalse($riwayat->isManualAction());

        // Other actions are manual
        $manualActions = ['disetujui', 'ditolak', 'dibatalkan'];
        foreach ($manualActions as $aksi) {
            $riwayat->setAksi($aksi);
            $this->assertFalse($riwayat->isSystemAction(), "'$aksi' should not be system action");
            $this->assertTrue($riwayat->isManualAction(), "'$aksi' should be manual action");
        }
    }

    /**
     * Test getTimeSinceAction method
     */
    public function testGetTimeSinceAction(): void
    {
        $riwayat = new RiwayatCuti();

        // Test with current time (should be "Baru saja")
        $riwayat->setTanggalAksi(new \DateTimeImmutable());
        $this->assertEquals('Baru saja', $riwayat->getTimeSinceAction());

        // Test with 30 minutes ago
        $riwayat->setTanggalAksi(new \DateTimeImmutable('-30 minutes'));
        $this->assertEquals('30 menit yang lalu', $riwayat->getTimeSinceAction());

        // Test with 2 hours ago
        $riwayat->setTanggalAksi(new \DateTimeImmutable('-2 hours'));
        $this->assertEquals('2 jam yang lalu', $riwayat->getTimeSinceAction());

        // Test with 3 days ago
        $riwayat->setTanggalAksi(new \DateTimeImmutable('-3 days'));
        $this->assertEquals('3 hari yang lalu', $riwayat->getTimeSinceAction());

        // Test with 1 hour ago
        $riwayat->setTanggalAksi(new \DateTimeImmutable('-1 hour'));
        $this->assertEquals('1 jam yang lalu', $riwayat->getTimeSinceAction());

        // Test with 1 minute ago  
        $riwayat->setTanggalAksi(new \DateTimeImmutable('-1 minute'));
        $this->assertEquals('1 menit yang lalu', $riwayat->getTimeSinceAction());
    }

    /**
     * Test __toString method
     */
    public function testToStringMethod(): void
    {
        $user = $this->createTestUser();
        $pengajuan = $this->createTestPengajuanCuti();

        $riwayat = RiwayatCuti::createRiwayat($pengajuan, $user, 'disetujui');

        $string = (string) $riwayat;

        $this->assertStringContainsString('Disetujui', $string, 'ToString should contain action label');
        $this->assertStringContainsString('Test User', $string, 'ToString should contain user name');
        
        // Should contain formatted date
        $expectedDate = $riwayat->getTanggalAksi()->format('d/m/Y H:i');
        $this->assertStringContainsString($expectedDate, $string, 'ToString should contain formatted date');
    }

    /**
     * Test validation - required fields
     */
    public function testValidationRequiredFields(): void
    {
        $riwayat = new RiwayatCuti();
        
        $errors = $this->validator->validate($riwayat);
        $this->assertGreaterThan(0, $errors->count(), 'Empty riwayat should have validation errors');

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
        }

        // Check that required fields are validated
        $hasAksiError = false;
        $hasPengajuanError = false;
        $hasUserError = false;

        foreach ($errorMessages as $message) {
            if (strpos($message, 'aksi') !== false) $hasAksiError = true;
            if (strpos($message, 'pengajuanCuti') !== false) $hasPengajuanError = true;
            if (strpos($message, 'user') !== false) $hasUserError = true;
        }

        $this->assertTrue($hasAksiError || $hasPengajuanError || $hasUserError, 
            'Should have validation errors for required fields');
    }

    /**
     * Test validation - valid riwayat
     */
    public function testValidationValidRiwayat(): void
    {
        $pengajuan = $this->createTestPengajuanCuti();
        $user = $this->createTestUser();

        $riwayat = RiwayatCuti::createRiwayat($pengajuan, $user, 'diajukan', 'Test catatan');

        $errors = $this->validator->validate($riwayat);
        $this->assertCount(0, $errors, 'Valid riwayat should have no validation errors');
    }

    /**
     * Test aksi length constraint
     */
    public function testAksiLengthConstraint(): void
    {
        $pengajuan = $this->createTestPengajuanCuti();
        $user = $this->createTestUser();

        $riwayat = RiwayatCuti::createRiwayat($pengajuan, $user, str_repeat('a', 25), 'Test'); // 25 chars, max is 20

        $errors = $this->validator->validate($riwayat);
        $this->assertGreaterThan(0, $errors->count(), 'Aksi longer than 20 chars should have validation error');
    }

    /**
     * Test comprehensive workflow tracking
     */
    public function testComprehensiveWorkflowTracking(): void
    {
        $pengajuan = $this->createTestPengajuanCuti();
        $user = $this->createTestUser();
        $approver = $this->createTestUser();
        $approver->setNama('Approver User')->setNip('198001012015021001');

        // Create complete workflow riwayat
        $riwayatList = [];

        // 1. Diajukan
        $riwayatList[] = RiwayatCuti::createRiwayat($pengajuan, $user, 'diajukan');

        // 2. Disetujui
        $riwayatList[] = RiwayatCuti::createRiwayat($pengajuan, $approver, 'disetujui', 'Memenuhi syarat');

        // 3. Dibatalkan (user change mind)
        $riwayatList[] = RiwayatCuti::createRiwayat($pengajuan, $user, 'dibatalkan', 'Ada kendala mendadak');

        $this->assertCount(3, $riwayatList, 'Should have 3 riwayat entries for complete workflow');

        // Verify each entry
        $this->assertTrue($riwayatList[0]->isDiajukan());
        $this->assertTrue($riwayatList[1]->isDisetujui());
        $this->assertTrue($riwayatList[2]->isDibatalkan());

        // Verify different users performed different actions
        $this->assertEquals($user, $riwayatList[0]->getUser());
        $this->assertEquals($approver, $riwayatList[1]->getUser());
        $this->assertEquals($user, $riwayatList[2]->getUser());

        // Verify catatan recording
        $this->assertFalse($riwayatList[0]->hasCatatan());
        $this->assertTrue($riwayatList[1]->hasCatatan());
        $this->assertTrue($riwayatList[2]->hasCatatan());

        $this->assertEquals('Memenuhi syarat', $riwayatList[1]->getCatatan());
        $this->assertEquals('Ada kendala mendadak', $riwayatList[2]->getCatatan());
    }

    /**
     * Test edge cases for time calculations
     */
    public function testEdgeCasesForTimeCalculations(): void
    {
        $riwayat = new RiwayatCuti();

        // Test exactly 1 minute ago
        $oneMinuteAgo = (new \DateTimeImmutable())->modify('-1 minute');
        $riwayat->setTanggalAksi($oneMinuteAgo);
        $result = $riwayat->getTimeSinceAction();
        $this->assertStringContainsString('menit yang lalu', $result);

        // Test exactly 1 hour ago
        $oneHourAgo = (new \DateTimeImmutable())->modify('-1 hour');
        $riwayat->setTanggalAksi($oneHourAgo);
        $result = $riwayat->getTimeSinceAction();
        $this->assertStringContainsString('jam yang lalu', $result);

        // Test exactly 1 day ago
        $oneDayAgo = (new \DateTimeImmutable())->modify('-1 day');
        $riwayat->setTanggalAksi($oneDayAgo);
        $result = $riwayat->getTimeSinceAction();
        $this->assertStringContainsString('hari yang lalu', $result);
    }
}