<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\JenisCuti;
use App\Service\CutiPolicy;
use App\Service\CutiCalculator;
use App\Repository\PengajuanCutiRepository;
use App\Model\ValidationResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CutiPolicyTest extends TestCase
{
    private CutiPolicy $cutiPolicy;
    private MockObject $cutiCalculator;
    private MockObject $pengajuanCutiRepository;

    protected function setUp(): void
    {
        $this->cutiCalculator = $this->createMock(CutiCalculator::class);
        $this->pengajuanCutiRepository = $this->createMock(PengajuanCutiRepository::class);
        
        $this->cutiPolicy = new CutiPolicy(
            $this->cutiCalculator,
            $this->pengajuanCutiRepository
        );
    }

    public function testDapatAjukan_ValidCutiTahunan(): void
    {
        $user = $this->createPNSUser();
        $jenisCuti = $this->createJenisCuti('CT', 'Cuti Tahunan', 12, 'ALL');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-01-17');
        
        // Mock calculations
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(3);
        
        $this->cutiCalculator
            ->method('hitungMasaKerja')
            ->willReturn(5); // Sufficient service
        
        $this->cutiCalculator
            ->method('hitungTotalHakTahunBerjalan')
            ->willReturn(12); // Sufficient quota
        
        // No overlapping cuti
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getFirstError());
    }

    public function testDapatAjukan_PPPKCannotUseCutiBesar(): void
    {
        $user = $this->createPPPKUser();
        $jenisCuti = $this->createJenisCuti('CB', 'Cuti Besar', 90, 'PNS');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-04-15');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(90);
        
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Jenis cuti Cuti Besar tidak tersedia untuk PPPK', $result->getFirstError());
    }

    public function testDapatAjukan_ExceedDuration(): void
    {
        $user = $this->createPNSUser();
        $jenisCuti = $this->createJenisCuti('CAP', 'Cuti Alasan Penting', 30, 'ALL');
        
        $mulai = new \DateTime('2024-01-01');
        $selesai = new \DateTime('2024-02-15'); // 45+ days
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(45);
        
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('melebihi maksimal 30 hari', $result->getFirstError());
    }

    public function testDapatAjukan_InsufficientMasaKerja(): void
    {
        $user = $this->createPNSUser();
        $jenisCuti = $this->createJenisCuti('CT', 'Cuti Tahunan', 12, 'ALL');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-01-17');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(3);
        
        $this->cutiCalculator
            ->method('hitungMasaKerja')
            ->willReturn(0); // Insufficient service
        
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Cuti tahunan memerlukan masa kerja minimal 1 tahun', $result->getFirstError());
    }

    public function testDapatAjukan_InsufficientQuota(): void
    {
        $user = $this->createPNSUser();
        $jenisCuti = $this->createJenisCuti('CT', 'Cuti Tahunan', 12, 'ALL');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-01-25');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(10);
        
        $this->cutiCalculator
            ->method('hitungMasaKerja')
            ->willReturn(5);
        
        $this->cutiCalculator
            ->method('hitungTotalHakTahunBerjalan')
            ->willReturn(5); // Less than required
        
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Sisa hak cuti tahunan tidak mencukupi', $result->getFirstError());
    }

    public function testDapatAjukan_OverlappingCuti(): void
    {
        $user = $this->createPNSUser();
        $jenisCuti = $this->createJenisCuti('CT', 'Cuti Tahunan', 12, 'ALL');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-01-17');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(3);
        
        $this->cutiCalculator
            ->method('hitungMasaKerja')
            ->willReturn(5);
        
        $this->cutiCalculator
            ->method('hitungTotalHakTahunBerjalan')
            ->willReturn(12);
        
        // Has overlapping cuti
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn(['some_overlap']);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Terdapat overlap dengan pengajuan cuti yang sudah ada', $result->getFirstError());
    }

    public function testValidasiDurasi_NoDurationLimit(): void
    {
        $jenisCuti = new JenisCuti();
        $jenisCuti->setKode('CS');
        $jenisCuti->setNama('Cuti Sakit');
        $jenisCuti->setDurasiMax(null); // No limit
        
        $result = $this->cutiPolicy->validasiDurasi($jenisCuti, 100);
        
        $this->assertTrue($result->isValid());
    }

    public function testValidasiDurasi_WithinLimit(): void
    {
        $jenisCuti = new JenisCuti();
        $jenisCuti->setKode('CT');
        $jenisCuti->setNama('Cuti Tahunan');
        $jenisCuti->setDurasiMax(12);
        
        $result = $this->cutiPolicy->validasiDurasi($jenisCuti, 10);
        
        $this->assertTrue($result->isValid());
    }

    public function testValidasiDurasi_ExceedLimit(): void
    {
        $jenisCuti = new JenisCuti();
        $jenisCuti->setKode('CT');
        $jenisCuti->setNama('Cuti Tahunan');
        $jenisCuti->setDurasiMax(12);
        
        $result = $this->cutiPolicy->validasiDurasi($jenisCuti, 15);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('melebihi maksimal 12 hari', $result->getFirstError());
    }

    public function testValidasiCutiMelahirkan_MaleUser(): void
    {
        $user = $this->createPNSUser();
        $user->setJenisKelamin('L'); // Male
        $jenisCuti = $this->createJenisCuti('CM', 'Cuti Melahirkan', 90, 'ALL');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-04-15');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(90);
        
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Cuti melahirkan hanya untuk pegawai perempuan', $result->getFirstError());
    }

    public function testValidasiCutiMelahirkan_TooManyChildren(): void
    {
        $user = $this->createPNSUser();
        $user->setJenisKelamin('P'); // Female
        $user->setJumlahAnak(4); // 4th child
        $jenisCuti = $this->createJenisCuti('CM', 'Cuti Melahirkan', 90, 'ALL');
        
        $mulai = new \DateTime('2024-01-15');
        $selesai = new \DateTime('2024-04-15');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(90);
        
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);
        
        $result = $this->cutiPolicy->dapatAjukan($user, $jenisCuti, $mulai, $selesai);
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('anak ke-4 dan seterusnya', $result->getFirstError());
    }

    private function createPNSUser(): User
    {
        $user = new User();
        $user->setStatusKepegawaian('PNS');
        $user->setJenisKelamin('L');
        $user->setTmtCpns(new \DateTime('2020-01-01'));
        $user->setJumlahAnak(1);
        return $user;
    }

    private function createPPPKUser(): User
    {
        $user = new User();
        $user->setStatusKepegawaian('PPPK');
        $user->setJenisKelamin('L');
        $user->setJumlahAnak(1);
        return $user;
    }

    private function createJenisCuti(string $kode, string $nama, ?int $durasiMax, string $tersediaUntuk): JenisCuti
    {
        $jenisCuti = new JenisCuti();
        $jenisCuti->setKode($kode);
        $jenisCuti->setNama($nama);
        $jenisCuti->setDurasiMax($durasiMax);
        $jenisCuti->setTersediUntuk($tersediaUntuk);
        return $jenisCuti;
    }

    /**
     * Test: PNS boleh CB & CLTN; PPPK tidak (sesuai permintaan user)
     */
    public function testPNS_BolehCB_dan_CLTN_PPPK_Tidak(): void
    {
        $pnsUser = $this->createPNSUser();
        $pppkUser = $this->createPPPKUser();
        
        $cutiBesar = $this->createJenisCuti('CB', 'Cuti Besar', 90, 'PNS');
        $cltn = $this->createJenisCuti('CLTN', 'Cuti di Luar Tanggungan Negara', 365, 'PNS');
        
        $mulai = new \DateTime('2025-01-01');
        $selesai = new \DateTime('2025-01-30');

        // Mock masa kerja yang cukup untuk PNS
        $this->cutiCalculator
            ->method('hitungMasaKerja')
            ->willReturn(10); // 10 tahun masa kerja

        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(30);

        // Mock tidak ada overlap
        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);

        // Test PNS bisa CB
        $resultPnsCB = $this->cutiPolicy->dapatAjukan($pnsUser, $cutiBesar, $mulai, $selesai);
        $this->assertTrue($resultPnsCB->isValid(), 'PNS harus bisa mengajukan Cuti Besar');

        // Test PNS bisa CLTN  
        $resultPnsCltn = $this->cutiPolicy->dapatAjukan($pnsUser, $cltn, $mulai, $selesai);
        $this->assertTrue($resultPnsCltn->isValid(), 'PNS harus bisa mengajukan CLTN');

        // Test PPPK tidak bisa CB
        $resultPppkCB = $this->cutiPolicy->dapatAjukan($pppkUser, $cutiBesar, $mulai, $selesai);
        $this->assertFalse($resultPppkCB->isValid(), 'PPPK tidak boleh mengajukan Cuti Besar');
        $this->assertStringContainsString('Jenis cuti Cuti Besar tidak tersedia untuk PPPK', $resultPppkCB->getErrors());

        // Test PPPK tidak bisa CLTN
        $resultPppkCltn = $this->cutiPolicy->dapatAjukan($pppkUser, $cltn, $mulai, $selesai);
        $this->assertFalse($resultPppkCltn->isValid(), 'PPPK tidak boleh mengajukan CLTN');
        $this->assertStringContainsString('Jenis cuti Cuti di Luar Tanggungan Negara tidak tersedia untuk PPPK', $resultPppkCltn->getErrors());
    }

    /**
     * Test: Cuti Alasan Penting ≤ 1 bulan (sesuai permintaan user)
     */
    public function testCutiAlasanPenting_Maksimal1Bulan(): void
    {
        $user = $this->createPNSUser();
        $cutiAlasanPenting = $this->createJenisCuti('CAP', 'Cuti Alasan Penting', 30, 'ALL');
        
        // Test durasi valid (30 hari)
        $mulai = new \DateTime('2025-01-01');
        $selesai = new \DateTime('2025-01-30');
        
        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(30);

        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);

        $result = $this->cutiPolicy->dapatAjukan($user, $cutiAlasanPenting, $mulai, $selesai);
        $this->assertTrue($result->isValid(), 'CAP 30 hari harus valid');

        // Test durasi invalid (31 hari) - akan gagal di validasi durasi
        $selesaiTerlalu = new \DateTime('2025-01-31');
        
        // Create new CutiPolicy with fresh mock for second test  
        $cutiCalc31 = $this->createMock(CutiCalculator::class);
        $cutiCalc31->method('hitungLamaCuti')->willReturn(31);
        $cutiPolicy31 = new CutiPolicy($cutiCalc31, $this->pengajuanCutiRepository);

        $result = $cutiPolicy31->dapatAjukan($user, $cutiAlasanPenting, $mulai, $selesaiTerlalu);
        $this->assertFalse($result->isValid(), 'CAP 31 hari harus invalid');
        $this->assertStringContainsString('melebihi maksimal 30 hari', $result->getFirstError());
    }

    /**
     * Test: Melahirkan anak ke-4 → diarahkan ke Cuti Besar (sesuai permintaan user)
     */
    public function testMelahirkan_AnakKe4_DiarahkanKeCutiBesar(): void
    {
        // User perempuan dengan 4 anak (akan melahirkan anak ke-5)
        $userPerempuan = $this->createPNSUser();
        $userPerempuan->setJenisKelamin('P');
        $userPerempuan->setJumlahAnak(4); // Sudah ada 4 anak
        
        $cutiMelahirkan = $this->createJenisCuti('CM', 'Cuti Melahirkan', 90, 'ALL');
        
        $mulai = new \DateTime('2025-01-01');
        $selesai = new \DateTime('2025-03-01');

        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(60);

        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);

        $result = $this->cutiPolicy->dapatAjukan($userPerempuan, $cutiMelahirkan, $mulai, $selesai);
        
        $this->assertFalse($result->isValid(), 'Cuti melahirkan untuk anak ke-4+ harus invalid');
        $this->assertStringContainsString('anak ke-4 dan seterusnya', $result->getFirstError());
    }

    /**
     * Test: Cuti Melahirkan valid untuk anak ke-1 sampai ke-3
     */
    public function testCutiMelahirkan_ValidUntukAnak1sampai3(): void
    {
        // Test untuk anak ke-3
        $userPerempuan = $this->createPNSUser(); 
        $userPerempuan->setJenisKelamin('P');
        $userPerempuan->setJumlahAnak(2); // Akan melahirkan anak ke-3
        
        $cutiMelahirkan = $this->createJenisCuti('CM', 'Cuti Melahirkan', 90, 'ALL');
        
        $mulai = new \DateTime('2025-01-01');
        $selesai = new \DateTime('2025-03-01');

        $this->cutiCalculator
            ->method('hitungLamaCuti')
            ->willReturn(60);

        $this->pengajuanCutiRepository
            ->method('findOverlappingCuti')
            ->willReturn([]);

        $result = $this->cutiPolicy->dapatAjukan($userPerempuan, $cutiMelahirkan, $mulai, $selesai);
        
        $this->assertTrue($result->isValid(), 'Cuti melahirkan untuk anak ke-1..3 harus valid');
    }
}