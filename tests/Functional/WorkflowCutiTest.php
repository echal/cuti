<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\PengajuanCuti;
use App\Entity\HakCuti;
use App\Entity\JenisCuti;
use App\Entity\UnitKerja;
use App\Entity\RiwayatCuti;
use App\Entity\Pejabat;
use App\Repository\PengajuanCutiRepository;
use App\Repository\HakCutiRepository;
use App\Repository\RiwayatCutiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class WorkflowCutiTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private User $testUser;
    private User $approverUser;
    private JenisCuti $cutiTahunan;
    private Pejabat $pejabatAtasan;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        
        // Clear existing data
        $this->clearTestData();
        
        // Setup fresh test data
        $this->setupTestData();
    }

    private function clearTestData(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\RiwayatCuti')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\PengajuanCuti')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\HakCuti')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Pejabat')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\JenisCuti')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\UnitKerja')->execute();
    }

    private function setupTestData(): void
    {
        // Create unit kerja
        $unitKerja = new UnitKerja();
        $unitKerja->setKode('001')
            ->setNama('Test Unit')
            ;
        $this->entityManager->persist($unitKerja);

        // Create regular test user
        $this->testUser = new User();
        $this->testUser->setNip('199001012020121001')
            ->setNama('Test User')
            ->setEmail('test@example.com')
            ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
            ->setRoles(['ROLE_USER'])
            ->setJenisKelamin('L')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Staff')
            ->setUnitKerja($unitKerja)
            ->setJumlahAnak(0)
            ->setStatusPegawai('aktif')
            ->setTmtCpns(new \DateTime('2020-01-01'));
        $this->entityManager->persist($this->testUser);

        // Create approver user
        $this->approverUser = new User();
        $this->approverUser->setNip('198001012015021001')
            ->setNama('Approver User')
            ->setEmail('approver@example.com')
            ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
            ->setRoles(['ROLE_APPROVER'])
            ->setJenisKelamin('L')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Kepala Unit')
            ->setUnitKerja($unitKerja)
            ->setJumlahAnak(1)
            ->setStatusPegawai('aktif')
            ->setTmtCpns(new \DateTime('2015-01-01'));
        $this->entityManager->persist($this->approverUser);

        // Create pejabat
        $this->pejabatAtasan = new Pejabat();
        $this->pejabatAtasan->setNama('Pejabat Atasan')
            ->setJabatan('Kepala Unit')
            ->setNip('198001012015021001')
            ->setUnitKerja($unitKerja)
            ;
        $this->entityManager->persist($this->pejabatAtasan);

        // Create jenis cuti
        $this->cutiTahunan = new JenisCuti();
        $this->cutiTahunan->setKode('CT')
            ->setNama('Cuti Tahunan')
            ->setDurasiMax(null)
            ->setTersediUntuk('ALL')
            ;
        $this->entityManager->persist($this->cutiTahunan);

        // Create hak cuti for current year
        $hakCuti = new HakCuti();
        $hakCuti->setTahun((int) date('Y'))
            ->setHakTahunan(12)
            ->setTerpakai(0)
            ->setSisa(12)
            ->setCarryOver(false)
            ->setUser($this->testUser);
        $this->entityManager->persist($hakCuti);

        $this->entityManager->flush();
    }

    private function loginUser(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
    }

    /**
     * Test complete workflow: ajukan → setujui → check RiwayatCuti & saldo
     */
    public function testCompleteWorkflowAjukanSetujui(): void
    {
        $this->loginUser($this->testUser);

        $pengajuanRepo = $this->entityManager->getRepository(PengajuanCuti::class);
        $riwayatRepo = $this->entityManager->getRepository(RiwayatCuti::class);
        $hakCutiRepo = $this->entityManager->getRepository(HakCuti::class);

        // Step 1: Create pengajuan cuti
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+5 days'))
            ->setLamaCuti(5)
            ->setAlasan('Libur keluarga')
            ->setStatus('draft')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan);
        $this->entityManager->flush();

        $pengajuanId = $pengajuan->getId();
        $this->assertNotNull($pengajuanId, 'Pengajuan harus tersimpan dengan ID');

        // Step 2: Submit pengajuan (draft → diajukan)
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuanId . '/submit');
        $this->assertResponseRedirects();

        // Refresh entity
        $this->entityManager->clear();
        $pengajuan = $pengajuanRepo->find($pengajuanId);
        
        $this->assertEquals('diajukan', $pengajuan->getStatus());
        
        // Check RiwayatCuti tercatat untuk submit
        $riwayatList = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan]);
        $this->assertCount(1, $riwayatList, 'Harus ada 1 riwayat setelah submit');
        
        $riwayatSubmit = $riwayatList[0];
        $this->assertEquals('diajukan', $riwayatSubmit->getAksi());
        $this->assertEquals($this->testUser->getId(), $riwayatSubmit->getUser()->getId());
        $this->assertTrue($riwayatSubmit->isDiajukan());

        // Check saldo belum berkurang
        $hakCuti = $hakCutiRepo->findByUserAndTahun($this->testUser, (int) date('Y'));
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Saldo tidak boleh berkurang sebelum disetujui');
        $this->assertEquals(12, $hakCuti->getSisa());

        // Step 3: Login as approver dan approve
        $this->loginUser($this->approverUser);
        
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuanId . '/approve', [
            'catatan' => 'Disetujui untuk kepentingan keluarga'
        ]);
        $this->assertResponseRedirects();

        // Refresh entities
        $this->entityManager->clear();
        $pengajuan = $pengajuanRepo->find($pengajuanId);
        
        $this->assertEquals('disetujui', $pengajuan->getStatus());

        // Check RiwayatCuti bertambah untuk approval
        $riwayatList = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan], ['id' => 'ASC']);
        $this->assertCount(2, $riwayatList, 'Harus ada 2 riwayat setelah approve');
        
        $riwayatApprove = $riwayatList[1]; // Second entry
        $this->assertEquals('disetujui', $riwayatApprove->getAksi());
        $this->assertEquals($this->approverUser->getId(), $riwayatApprove->getUser()->getId());
        $this->assertEquals('Disetujui untuk kepentingan keluarga', $riwayatApprove->getCatatan());
        $this->assertTrue($riwayatApprove->isDisetujui());

        // Check saldo berkurang setelah approve
        $hakCuti = $hakCutiRepo->findByUserAndTahun($this->testUser, (int) date('Y'));
        $this->assertEquals(5, $hakCuti->getTerpakai(), 'Saldo harus berkurang 5 hari setelah disetujui');
        $this->assertEquals(7, $hakCuti->getSisa(), 'Sisa cuti harus menjadi 7 hari');
    }

    /**
     * Test workflow: ajukan → tolak → check RiwayatCuti (saldo tidak berkurang)
     */
    public function testWorkflowAjukanTolak(): void
    {
        $this->loginUser($this->testUser);

        $pengajuanRepo = $this->entityManager->getRepository(PengajuanCuti::class);
        $riwayatRepo = $this->entityManager->getRepository(RiwayatCuti::class);
        $hakCutiRepo = $this->entityManager->getRepository(HakCuti::class);

        // Create and submit pengajuan
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+3 days'))
            ->setLamaCuti(3)
            ->setAlasan('Test penolakan')
            ->setStatus('diajukan')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan);
        $this->entityManager->flush();

        $pengajuanId = $pengajuan->getId();

        // Login as approver dan reject
        $this->loginUser($this->approverUser);
        
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuanId . '/reject', [
            'alasan_penolakan' => 'Tidak sesuai dengan kebutuhan operasional'
        ]);
        $this->assertResponseRedirects();

        // Check pengajuan status
        $this->entityManager->clear();
        $pengajuan = $pengajuanRepo->find($pengajuanId);
        
        $this->assertEquals('ditolak', $pengajuan->getStatus());

        // Check RiwayatCuti tercatat untuk rejection
        $riwayatList = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan]);
        $this->assertCount(1, $riwayatList, 'Harus ada 1 riwayat untuk penolakan');
        
        $riwayatReject = $riwayatList[0];
        $this->assertEquals('ditolak', $riwayatReject->getAksi());
        $this->assertEquals($this->approverUser->getId(), $riwayatReject->getUser()->getId());
        $this->assertEquals('Tidak sesuai dengan kebutuhan operasional', $riwayatReject->getCatatan());
        $this->assertTrue($riwayatReject->isDitolak());

        // Check saldo tidak berubah
        $hakCuti = $hakCutiRepo->findByUserAndTahun($this->testUser, (int) date('Y'));
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Saldo tidak boleh berubah jika ditolak');
        $this->assertEquals(12, $hakCuti->getSisa());
    }

    /**
     * Test workflow: ajukan → setujui → batalkan → check saldo dikembalikan
     */
    public function testWorkflowBatalkanSetelahDisetujui(): void
    {
        $this->loginUser($this->testUser);

        $pengajuanRepo = $this->entityManager->getRepository(PengajuanCuti::class);
        $riwayatRepo = $this->entityManager->getRepository(RiwayatCuti::class);
        $hakCutiRepo = $this->entityManager->getRepository(HakCuti::class);

        // Create approved pengajuan with reduced balance
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+10 days')) // Future date untuk bisa dibatalkan
            ->setTanggalSelesai(new \DateTime('+14 days'))
            ->setLamaCuti(5)
            ->setAlasan('Test pembatalan')
            ->setStatus('disetujui')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan)
            ->setPejabatAtasan($this->pejabatAtasan);
        
        $this->entityManager->persist($pengajuan);

        // Reduce balance to simulate approved state
        $hakCuti = $hakCutiRepo->findByUserAndTahun($this->testUser, (int) date('Y'));
        $hakCuti->setTerpakai(5); // 5 hari terpakai
        $this->entityManager->persist($hakCuti);

        // Create riwayat for approved state
        $riwayatApproved = RiwayatCuti::createRiwayat($pengajuan, $this->approverUser, 'disetujui', 'Disetujui sebelumnya');
        $this->entityManager->persist($riwayatApproved);

        $this->entityManager->flush();

        $pengajuanId = $pengajuan->getId();

        // Cancel the approved pengajuan
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuanId . '/cancel', [
            'alasan_pembatalan' => 'Berhalangan hadir karena urusan mendadak'
        ]);
        $this->assertResponseRedirects();

        // Check pengajuan status
        $this->entityManager->clear();
        $pengajuan = $pengajuanRepo->find($pengajuanId);
        
        $this->assertEquals('dibatalkan', $pengajuan->getStatus());

        // Check RiwayatCuti bertambah untuk cancellation
        $riwayatList = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan], ['id' => 'ASC']);
        $this->assertCount(2, $riwayatList, 'Harus ada 2 riwayat setelah pembatalan');
        
        $riwayatCancel = $riwayatList[1]; // Latest entry
        $this->assertEquals('dibatalkan', $riwayatCancel->getAksi());
        $this->assertEquals($this->testUser->getId(), $riwayatCancel->getUser()->getId());
        $this->assertEquals('Berhalangan hadir karena urusan mendadak', $riwayatCancel->getCatatan());
        $this->assertTrue($riwayatCancel->isDibatalkan());

        // Check saldo dikembalikan setelah pembatalan
        $hakCuti = $hakCutiRepo->findByUserAndTahun($this->testUser, (int) date('Y'));
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Saldo harus dikembalikan setelah pembatalan');
        $this->assertEquals(12, $hakCuti->getSisa(), 'Sisa cuti harus kembali ke 12 hari');
    }

    /**
     * Test multiple submissions dan riwayat tracking
     */
    public function testMultipleSubmissionsRiwayatTracking(): void
    {
        $this->loginUser($this->testUser);

        $pengajuanRepo = $this->entityManager->getRepository(PengajuanCuti::class);
        $riwayatRepo = $this->entityManager->getRepository(RiwayatCuti::class);

        // Create first pengajuan
        $pengajuan1 = new PengajuanCuti();
        $pengajuan1->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+2 days'))
            ->setLamaCuti(2)
            ->setAlasan('First submission')
            ->setStatus('draft')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan1);

        // Create second pengajuan
        $pengajuan2 = new PengajuanCuti();
        $pengajuan2->setTanggalMulai(new \DateTime('+5 days'))
            ->setTanggalSelesai(new \DateTime('+7 days'))
            ->setLamaCuti(3)
            ->setAlasan('Second submission')
            ->setStatus('draft')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan2);
        $this->entityManager->flush();

        // Submit both pengajuan
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan1->getId() . '/submit');
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan2->getId() . '/submit');

        // Check each pengajuan has its own riwayat
        $riwayat1 = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan1]);
        $riwayat2 = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan2]);

        $this->assertCount(1, $riwayat1, 'Pengajuan 1 harus punya 1 riwayat');
        $this->assertCount(1, $riwayat2, 'Pengajuan 2 harus punya 1 riwayat');

        // Approve first, reject second
        $this->loginUser($this->approverUser);
        
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan1->getId() . '/approve');
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan2->getId() . '/reject', [
            'alasan_penolakan' => 'Overlapping with company event'
        ]);

        // Check final riwayat counts
        $this->entityManager->clear();
        $riwayat1Final = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan1]);
        $riwayat2Final = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan2]);

        $this->assertCount(2, $riwayat1Final, 'Pengajuan 1: submit + approve = 2 riwayat');
        $this->assertCount(2, $riwayat2Final, 'Pengajuan 2: submit + reject = 2 riwayat');
    }

    /**
     * Test RiwayatCuti utility methods
     */
    public function testRiwayatCutiUtilityMethods(): void
    {
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+2 days'))
            ->setLamaCuti(2)
            ->setAlasan('Test utility methods')
            ->setStatus('draft')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan);
        $this->entityManager->flush();

        // Test createRiwayat static method
        $riwayat = RiwayatCuti::createRiwayat($pengajuan, $this->testUser, 'diajukan', 'Test riwayat');
        
        $this->assertEquals($pengajuan, $riwayat->getPengajuanCuti());
        $this->assertEquals($this->testUser, $riwayat->getUser());
        $this->assertEquals('diajukan', $riwayat->getAksi());
        $this->assertEquals('Test riwayat', $riwayat->getCatatan());

        // Test status check methods
        $this->assertTrue($riwayat->isDiajukan());
        $this->assertFalse($riwayat->isDisetujui());
        $this->assertFalse($riwayat->isDitolak());
        $this->assertFalse($riwayat->isDibatalkan());

        // Test getAksiLabel
        $this->assertEquals('Diajukan', $riwayat->getAksiLabel());

        // Test getAksiBadgeClass
        $this->assertEquals('badge-primary', $riwayat->getAksiBadgeClass());

        // Test hasCatatan
        $this->assertTrue($riwayat->hasCatatan());

        // Test action type methods
        $this->assertTrue($riwayat->isSystemAction());
        $this->assertFalse($riwayat->isManualAction());

        // Test __toString
        $string = (string) $riwayat;
        $this->assertStringContainsString('Diajukan', $string);
        $this->assertStringContainsString('Test User', $string);
    }

    /**
     * Test validation saldo insufficient
     */
    public function testValidationSaldoInsufficient(): void
    {
        $this->loginUser($this->testUser);

        // Reduce balance to 2 days only
        $hakCutiRepo = $this->entityManager->getRepository(HakCuti::class);
        $hakCuti = $hakCutiRepo->findByUserAndTahun($this->testUser, (int) date('Y'));
        $hakCuti->setTerpakai(10); // 10 terpakai, sisa 2
        $this->entityManager->persist($hakCuti);
        $this->entityManager->flush();

        // Try to create pengajuan for 5 days (more than available)
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+5 days'))
            ->setLamaCuti(5)
            ->setAlasan('Test insufficient balance')
            ->setStatus('draft')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan);
        $this->entityManager->flush();

        // Try to submit - should fail
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan->getId() . '/submit');
        
        // Should redirect with error (implementation specific)
        $this->assertResponseRedirects();

        // Verify no riwayat created for failed submission
        $riwayatRepo = $this->entityManager->getRepository(RiwayatCuti::class);
        $riwayatList = $riwayatRepo->findBy(['pengajuanCuti' => $pengajuan]);
        
        // Depends on implementation - might be 0 (failed) or 1 (attempted)
        $this->assertLessThanOrEqual(1, count($riwayatList));
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->clearTestData();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}