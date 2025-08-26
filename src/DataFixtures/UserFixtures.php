<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            // Admin Users
            [
                'nip' => '199001012020121001',
                'nama' => 'Administrator System',
                'email' => 'admin@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_ADMIN'],
                'unitKerja' => 'unit-kerja-tu',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Kepala Sub Bagian TU',
            ],

            // Approver Users (Kepala Bidang)
            [
                'nip' => '198901012014121001',
                'nama' => 'Dr. H. Abdul Rahman, S.Ag, M.H',
                'email' => 'kabid.bimas@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_APPROVER'],
                'unitKerja' => 'unit-kerja-bimas',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Kepala Bidang Bimas Islam',
            ],
            [
                'nip' => '198505142009032001',
                'nama' => 'Hj. Siti Aminah, S.Pd, M.Pd',
                'email' => 'kabid.mad@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_APPROVER'],
                'unitKerja' => 'unit-kerja-mad',
                'jenisKelamin' => 'P',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Kepala Bidang Madrasah',
            ],
            [
                'nip' => '197908122010121002',
                'nama' => 'Drs. Muhammad Yusuf, M.Pd.I',
                'email' => 'kabid.papkis@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_APPROVER'],
                'unitKerja' => 'unit-kerja-papkis',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Kepala Bidang PAPKIS',
            ],

            // Regular Users (Staff)
            [
                'nip' => '199205102022021001',
                'nama' => 'Abdul Rahman, S.Pd.I',
                'email' => 'abdul.rahman@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-bimas',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Staff Bimas Islam',
            ],
            [
                'nip' => '199712252019032001',
                'nama' => 'Fatimah Zahra, S.Ag, M.H',
                'email' => 'fatimah.zahra@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-mad',
                'jenisKelamin' => 'P',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Staff Madrasah',
            ],
            [
                'nip' => '199003152020121003',
                'nama' => 'Ahmad Hidayat, S.Pd',
                'email' => 'ahmad.hidayat@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-papkis',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PPPK',
                'jabatan' => 'Staff PAPKIS',
            ],
            [
                'nip' => '199106182021032002',
                'nama' => 'Nur Hidayah, S.Ag',
                'email' => 'nur.hidayah@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-phu',
                'jenisKelamin' => 'P',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Staff PHU',
            ],
            [
                'nip' => '198812102018121001',
                'nama' => 'Dra. Maria Magdalena',
                'email' => 'maria.magdalena@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-kristen',
                'jenisKelamin' => 'P',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Staff Kristen',
            ],
            [
                'nip' => '199504232022021004',
                'nama' => 'Antonius Suryadi, S.Th',
                'email' => 'antonius.suryadi@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-katolik',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PPPK',
                'jabatan' => 'Staff Katolik',
            ],
            [
                'nip' => '198709152015032001',
                'nama' => 'Ni Made Sari Dewi, S.Ag',
                'email' => 'made.sari@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-hindu',
                'jenisKelamin' => 'P',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Staff Hindu',
            ],
            [
                'nip' => '199602102020121005',
                'nama' => 'Thich Minh An, S.Ag',
                'email' => 'minh.an@kanwilsulbar.kemenag.go.id',
                'roles' => ['ROLE_USER'],
                'unitKerja' => 'unit-kerja-buddha',
                'jenisKelamin' => 'L',
                'statusKepegawaian' => 'PNS',
                'jabatan' => 'Staff Buddha',
            ],
        ];

        foreach ($usersData as $userData) {
            $user = new User();
            
            // Set basic info
            $user->setNip($userData['nip'])
                 ->setNama($userData['nama'])
                 ->setEmail($userData['email'])
                 ->setRoles($userData['roles'])
                 ->setJenisKelamin($userData['jenisKelamin'])
                 ->setStatusKepegawaian($userData['statusKepegawaian'])
                 ->setJabatan($userData['jabatan'])
                 ->setJumlahAnak(0)
                 ->setStatusPegawai('aktif');

            // Set unit kerja from reference
            $unitKerja = $this->getReference($userData['unitKerja'], \App\Entity\UnitKerja::class);
            $user->setUnitKerja($unitKerja);

            // Hash password (NIP sebagai password default)
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['nip']);
            $user->setPassword($hashedPassword);

            // Set TMT untuk PNS
            if ($userData['statusKepegawaian'] === 'PNS') {
                $tmtCpns = new \DateTime('2010-01-01'); // Default TMT CPNS
                $user->setTmtCpns($tmtCpns);
                
                $tmtPns = new \DateTime('2012-01-01'); // Default TMT PNS
                $user->setTmtPns($tmtPns);
            }

            $manager->persist($user);

            // Add reference for later use
            $this->addReference('user-' . $userData['nip'], $user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UnitKerjaFixtures::class,
        ];
    }
}