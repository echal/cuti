<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\PengajuanCuti;
use App\Entity\HakCuti;
use App\Entity\JenisCuti;
use App\Entity\UnitKerja;
use App\Repository\HakCutiRepository;
use App\Service\CutiCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PengajuanCutiControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private User $testUser;
    private JenisCuti $cutiTahunan;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        
        // Setup test data
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        // Create unit kerja
        $unitKerja = new UnitKerja();
        $unitKerja->setKode('001')
            ->setNama('Test Unit')
            ;
        $this->entityManager->persist($unitKerja);

        // Create test user
        $this->testUser = new User();
        $this->testUser->setNip('199001012020121001')
            ->setNama('Test User')
            ->setEmail('test@example.com')
            ->setPassword('$2y$13$hashedpassword') // Hashed password
            ->setJenisKelamin('L')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Test Jabatan')
            ->setUnitKerja($unitKerja)
            ->setJumlahAnak(0)
            ->setStatusPegawai('aktif')
            ->setTmtCpns(new \DateTime('2020-01-01'));
        $this->entityManager->persist($this->testUser);

        // Create jenis cuti
        $this->cutiTahunan = new JenisCuti();
        $this->cutiTahunan->setKode('CT')
            ->setNama('Cuti Tahunan')
            ->setDurasiMax(null)
            ->setTersediUntuk('ALL');
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
     * Test: alur submit → approve → cek saldo berkurang (sesuai permintaan user)
     */
    public function testAlurSubmitApprove_CekSaldoBerkurang(): void
    {
        // Login as user
        $this->loginUser($this->testUser);

        // Step 1: Submit pengajuan cuti
        $crawler = $this->client->request('GET', '/pengajuan-cuti/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Simpan')->form();
        $form['pengajuan_cuti[jenisCuti]'] = $this->cutiTahunan->getId();
        $form['pengajuan_cuti[tanggalMulai]'] = (new \DateTime('+1 day'))->format('Y-m-d');
        $form['pengajuan_cuti[tanggalSelesai]'] = (new \DateTime('+5 days'))->format('Y-m-d');
        $form['pengajuan_cuti[alasan]'] = 'Test pengajuan cuti untuk testing';

        $this->client->submit($form);
        $this->assertResponseRedirects();

        // Verify pengajuan created with status 'draft'
        $pengajuan = $this->entityManager->getRepository(PengajuanCuti::class)
            ->findOneBy(['user' => $this->testUser]);
        
        $this->assertNotNull($pengajuan, 'Pengajuan cuti harus berhasil dibuat');
        $this->assertEquals('draft', $pengajuan->getStatus());
        $this->assertEquals(5, $pengajuan->getLamaCuti()); // 5 hari

        // Step 2: Submit pengajuan (change status to 'diajukan')
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan->getId() . '/submit');
        $this->assertResponseRedirects();

        $this->entityManager->refresh($pengajuan);
        $this->assertEquals('diajukan', $pengajuan->getStatus());

        // Verify saldo belum berkurang (masih dalam status 'diajukan')
        $hakCuti = $this->entityManager->getRepository(HakCuti::class)
            ->findByUserAndTahun($this->testUser, (int) date('Y'));
        
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Saldo tidak boleh berkurang sebelum disetujui');
        $this->assertEquals(12, $hakCuti->getSisa());

        // Step 3: Approve pengajuan (simulate admin approval)
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan->getId() . '/approve');
        $this->assertResponseRedirects();

        $this->entityManager->refresh($pengajuan);
        $this->assertEquals('disetujui', $pengajuan->getStatus());

        // Step 4: Verify saldo berkurang setelah approve
        $this->entityManager->refresh($hakCuti);
        
        $this->assertEquals(5, $hakCuti->getTerpakai(), 'Saldo harus berkurang 5 hari setelah disetujui');
        $this->assertEquals(7, $hakCuti->getSisa(), 'Sisa cuti harus menjadi 7 hari');
    }

    /**
     * Test pengajuan cuti dengan saldo tidak mencukupi
     */
    public function testPengajuanCuti_SaldoTidakMencukupi(): void
    {
        // Set saldo menjadi 2 hari saja
        $hakCuti = $this->entityManager->getRepository(HakCuti::class)
            ->findByUserAndTahun($this->testUser, (int) date('Y'));
        $hakCuti->setTerpakai(10); // Terpakai 10, sisa 2
        $this->entityManager->persist($hakCuti);
        $this->entityManager->flush();

        $this->loginUser($this->testUser);

        // Submit pengajuan 5 hari (lebih dari sisa 2 hari)
        $crawler = $this->client->request('GET', '/pengajuan-cuti/new');
        
        $form = $crawler->selectButton('Simpan')->form();
        $form['pengajuan_cuti[jenisCuti]'] = $this->cutiTahunan->getId();
        $form['pengajuan_cuti[tanggalMulai]'] = (new \DateTime('+1 day'))->format('Y-m-d');
        $form['pengajuan_cuti[tanggalSelesai]'] = (new \DateTime('+5 days'))->format('Y-m-d');
        $form['pengajuan_cuti[alasan]'] = 'Test pengajuan melebihi saldo';

        $this->client->submit($form);

        // Should show validation error
        $this->assertSelectorTextContains('.alert-danger', 'tidak mencukupi');
        
        // Verify no pengajuan created
        $pengajuan = $this->entityManager->getRepository(PengajuanCuti::class)
            ->findOneBy(['user' => $this->testUser]);
        
        $this->assertNull($pengajuan, 'Pengajuan tidak boleh dibuat jika saldo tidak cukup');
    }

    /**
     * Test reject pengajuan tidak mengurangi saldo
     */
    public function testRejectPengajuan_TidakMengurangiSaldo(): void
    {
        $this->loginUser($this->testUser);

        // Create and submit pengajuan
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+3 days'))
            ->setLamaCuti(3)
            ->setAlasan('Test reject')
            ->setStatus('diajukan')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan);
        $this->entityManager->flush();

        // Reject pengajuan
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan->getId() . '/reject', [
            'alasanPenolakan' => 'Alasan bisnis'
        ]);
        $this->assertResponseRedirects();

        $this->entityManager->refresh($pengajuan);
        $this->assertEquals('ditolak', $pengajuan->getStatus());

        // Verify saldo tidak berubah
        $hakCuti = $this->entityManager->getRepository(HakCuti::class)
            ->findByUserAndTahun($this->testUser, (int) date('Y'));
        
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Saldo tidak boleh berubah jika pengajuan ditolak');
        $this->assertEquals(12, $hakCuti->getSisa());
    }

    /**
     * Test batalkan pengajuan yang sudah disetujui (harus restore saldo)
     */
    public function testBatalkanPengajuanDisetujui_RestoreSaldo(): void
    {
        $this->loginUser($this->testUser);

        // Create approved pengajuan
        $pengajuan = new PengajuanCuti();
        $pengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+4 days'))
            ->setLamaCuti(4)
            ->setAlasan('Test batalkan')
            ->setStatus('disetujui')
            ->setUser($this->testUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($pengajuan);

        // Reduce balance (simulate approved state)
        $hakCuti = $this->entityManager->getRepository(HakCuti::class)
            ->findByUserAndTahun($this->testUser, (int) date('Y'));
        $hakCuti->setTerpakai(4); // 4 hari terpakai
        $this->entityManager->persist($hakCuti);
        
        $this->entityManager->flush();

        // Cancel pengajuan
        $this->client->request('POST', '/pengajuan-cuti/' . $pengajuan->getId() . '/cancel');
        $this->assertResponseRedirects();

        $this->entityManager->refresh($pengajuan);
        $this->assertEquals('dibatalkan', $pengajuan->getStatus());

        // Verify saldo dikembalikan
        $this->entityManager->refresh($hakCuti);
        $this->assertEquals(0, $hakCuti->getTerpakai(), 'Saldo harus dikembalikan setelah pembatalan');
        $this->assertEquals(12, $hakCuti->getSisa());
    }

    /**
     * Test access control - user hanya bisa lihat pengajuan sendiri
     */
    public function testAccessControl_UserHanyaBisaLihatPengajuanSendiri(): void
    {
        // Create another user
        $otherUser = new User();
        $otherUser->setNip('199002012020121001')
            ->setNama('Other User')
            ->setEmail('other@example.com')
            ->setPassword('$2y$13$hashedpassword')
            ->setJenisKelamin('P')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Other Jabatan')
            ->setUnitKerja($this->testUser->getUnitKerja())
            ->setJumlahAnak(0)
            ->setStatusPegawai('aktif')
            ->setTmtCpns(new \DateTime('2020-01-01'));
        $this->entityManager->persist($otherUser);

        // Create pengajuan for other user
        $otherPengajuan = new PengajuanCuti();
        $otherPengajuan->setTanggalMulai(new \DateTime('+1 day'))
            ->setTanggalSelesai(new \DateTime('+2 days'))
            ->setLamaCuti(2)
            ->setAlasan('Other user pengajuan')
            ->setStatus('draft')
            ->setUser($otherUser)
            ->setJenisCuti($this->cutiTahunan);
        
        $this->entityManager->persist($otherPengajuan);
        $this->entityManager->flush();

        $this->loginUser($this->testUser);

        // Try to access other user's pengajuan
        $this->client->request('GET', '/pengajuan-cuti/' . $otherPengajuan->getId());
        $this->assertResponseStatusCodeSame(403, 'User tidak boleh akses pengajuan user lain');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}