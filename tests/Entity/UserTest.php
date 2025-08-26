<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UnitKerja;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    private function createUnitKerja(): UnitKerja
    {
        $unitKerja = new UnitKerja();
        $unitKerja->setKode('001')
            ->setNama('Test Unit');
        
        return $unitKerja;
    }

    private function createValidUser(): User
    {
        $user = new User();
        $user->setNip('199001012020121001') // Valid 18 digit NIP
            ->setNama('Test User')
            ->setEmail('test@example.com')
            ->setPassword('TestPassword123')
            ->setJenisKelamin('L')
            ->setStatusKepegawaian('PNS')
            ->setJabatan('Test Jabatan')
            ->setUnitKerja($this->createUnitKerja())
            ->setJumlahAnak(0)
            ->setStatusPegawai('aktif');

        return $user;
    }

    /**
     * Test validasi NIP 18 digit - Valid
     */
    public function testValidNIP18Digit(): void
    {
        $user = $this->createValidUser();
        
        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors, 'NIP 18 digit valid seharusnya tidak ada error');
    }

    /**
     * Test validasi NIP kurang dari 18 digit
     */
    public function testInvalidNIPKurangDari18Digit(): void
    {
        $user = $this->createValidUser();
        $user->setNip('1990010120201'); // 13 digit
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'NIP kurang dari 18 digit harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('NIP harus berupa 18 digit angka', $errorMessages);
    }

    /**
     * Test validasi NIP lebih dari 18 digit
     */
    public function testInvalidNIPLebihDari18Digit(): void
    {
        $user = $this->createValidUser();
        $user->setNip('1990010120201210012'); // 19 digit
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'NIP lebih dari 18 digit harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('NIP harus berupa 18 digit angka', $errorMessages);
    }

    /**
     * Test validasi NIP mengandung karakter non-digit
     */
    public function testInvalidNIPMengandungNonDigit(): void
    {
        $user = $this->createValidUser();
        $user->setNip('19900101202012100A'); // Mengandung huruf A
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'NIP dengan karakter non-digit harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('NIP harus berupa 18 digit angka', $errorMessages);
    }

    /**
     * Test validasi NIP kosong
     */
    public function testInvalidNIPKosong(): void
    {
        $user = $this->createValidUser();
        $user->setNip('');
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'NIP kosong harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('NIP harus diisi', $errorMessages);
    }

    /**
     * Test validasi NIP null
     */
    public function testInvalidNIPNull(): void
    {
        $user = $this->createValidUser();
        $user->setNip(null);
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'NIP null harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('NIP harus diisi', $errorMessages);
    }

    /**
     * Test validasi custom callback validateNipNipt
     */
    public function testValidateNipNiptCallback(): void
    {
        $user = $this->createValidUser();
        $user->setNip('123456789012345678'); // Valid format but test callback
        
        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors, 'NIP format valid seharusnya lolos validasi callback');
    }

    /**
     * Test password validation (minimal 8 karakter)
     */
    public function testPasswordValidation(): void
    {
        $user = $this->createValidUser();
        $user->setPassword('Test1'); // Kurang dari 8 karakter
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'Password < 8 karakter harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('Password minimal 8 karakter', $errorMessages);
    }

    /**
     * Test password complexity validation
     */
    public function testPasswordComplexityValidation(): void
    {
        $user = $this->createValidUser();
        $user->setPassword('testpassword'); // Tidak ada huruf besar dan angka
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'Password tanpa kompleksitas harus error');
        
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        
        $this->assertContains('Password harus mengandung huruf kecil, huruf besar, dan angka', $errorMessages);
    }

    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        $user = $this->createValidUser();
        $user->setEmail('invalid-email');
        
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'Email format invalid harus error');
    }

    /**
     * Test UserInterface methods
     */
    public function testUserInterfaceMethods(): void
    {
        $user = $this->createValidUser();
        
        // Test getUserIdentifier returns NIP
        $this->assertEquals('199001012020121001', $user->getUserIdentifier());
        
        // Test getUsername returns same as getUserIdentifier
        $this->assertEquals($user->getUserIdentifier(), $user->getUsername());
        
        // Test getRoles includes ROLE_USER
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        
        // Test setRoles
        $user->setRoles(['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles); // Always includes ROLE_USER
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    /**
     * Test getIdentifier and getIdentifierType methods
     */
    public function testIdentifierMethods(): void
    {
        $user = $this->createValidUser();
        
        // Test getIdentifier returns NIP
        $this->assertEquals('199001012020121001', $user->getIdentifier());
        
        // Test getIdentifierType returns 'NIP'
        $this->assertEquals('NIP', $user->getIdentifierType());
        
        // Test with null NIP
        $user->setNip(null);
        $this->assertNull($user->getIdentifier());
        $this->assertEquals('ID', $user->getIdentifierType());
    }

    /**
     * Test lifecycle callbacks
     */
    public function testLifecycleCallbacks(): void
    {
        $user = new User();
        
        // Test timestamps are set in constructor
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        
        // Test that createdAt and updatedAt are close to now
        $now = new \DateTimeImmutable();
        $diff = $now->diff($user->getCreatedAt());
        $this->assertLessThan(5, $diff->s, 'CreatedAt should be set close to now');
        
        $diff = $now->diff($user->getUpdatedAt());
        $this->assertLessThan(5, $diff->s, 'UpdatedAt should be set close to now');
    }

    /**
     * Test collections initialization
     */
    public function testCollectionsInitialization(): void
    {
        $user = new User();
        
        // Test hakCutis collection is initialized
        $this->assertCount(0, $user->getHakCutis());
        
        // Test pengajuanCutis collection is initialized  
        $this->assertCount(0, $user->getPengajuanCutis());
    }

    /**
     * Test __toString method returns meaningful string
     */
    public function testToStringMethod(): void
    {
        $user = $this->createValidUser();
        
        $string = (string) $user;
        $this->assertNotEmpty($string);
        // Should contain user name or identifier
        $this->assertTrue(
            strpos($string, 'Test User') !== false || 
            strpos($string, '199001012020121001') !== false,
            'ToString should contain user name or NIP'
        );
    }

    /**
     * Test various NIP edge cases
     */
    public function testNIPEdgeCases(): void
    {
        $user = $this->createValidUser();
        
        // Test NIP dengan leading zeros
        $user->setNip('000001012020121001');
        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors, 'NIP dengan leading zeros harus valid');
        
        // Test NIP dengan angka semua sama
        $user->setNip('111111111111111111');
        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors, 'NIP dengan angka sama harus valid secara format');
        
        // Test NIP dengan spasi
        $user->setNip('199001012020121 01'); // Ada spasi
        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, $errors->count(), 'NIP dengan spasi harus error');
    }

    /**
     * Test field constraints
     */
    public function testFieldConstraints(): void
    {
        $user = $this->createValidUser();
        
        // Test nama required
        $user->setNama('');
        $errors = $this->validator->validate($user);
        $nipErrors = array_filter(iterator_to_array($errors), fn($error) => $error->getPropertyPath() === 'nama');
        $this->assertGreaterThan(0, count($nipErrors), 'Nama kosong harus error');
        
        // Test jabatan required
        $user = $this->createValidUser();
        $user->setJabatan('');
        $errors = $this->validator->validate($user);
        $jabatanErrors = array_filter(iterator_to_array($errors), fn($error) => $error->getPropertyPath() === 'jabatan');
        $this->assertGreaterThan(0, count($jabatanErrors), 'Jabatan kosong harus error');
    }
}