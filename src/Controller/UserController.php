<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\UnitKerjaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UnitKerjaRepository $unitKerjaRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $unitKerjaId = $request->query->get('unit_kerja');
        $status = $request->query->get('status');

        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.unitKerja', 'uk')
            ->orderBy('u.nama', 'ASC');

        if ($search) {
            $queryBuilder
                ->andWhere('u.nama LIKE :search OR u.nip LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($unitKerjaId) {
            $queryBuilder
                ->andWhere('u.unitKerja = :unitKerja')
                ->setParameter('unitKerja', $unitKerjaId);
        }

        if ($status) {
            $queryBuilder
                ->andWhere('u.statusKepegawaian = :status')
                ->setParameter('status', $status);
        }

        $users = $queryBuilder->getQuery()->getResult();
        $units = $this->unitKerjaRepository->findBy([], ['nama' => 'ASC']);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'units' => $units,
            'search' => $search,
            'unit_kerja_id' => $unitKerjaId,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi NIP jika diisi
            if ($user->getNip()) {
                $existingNip = $this->userRepository->findOneBy(['nip' => $user->getNip()]);
                if ($existingNip) {
                    $this->addFlash('error', 'NIP sudah digunakan');
                    return $this->render('admin/user/new.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
            }

            // Cek duplikasi email
            $existingEmail = $this->userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingEmail) {
                $this->addFlash('error', 'Email sudah digunakan');
                return $this->render('admin/user/new.html.twig', [
                    'user' => $user,
                    'form' => $form,
                ]);
            }

            // Hash password
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } elseif ($user->getNip()) {
                // Default password = NIP jika ada
                $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getNip());
                $user->setPassword($hashedPassword);
            } else {
                // Default password untuk PPPK tanpa NIP
                $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
                $user->setPassword($hashedPassword);
            }

            // Set default role
            if (empty($user->getRoles()) || $user->getRoles() === ['ROLE_USER']) {
                $user->setRoles(['ROLE_USER']);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'User berhasil ditambahkan');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cek duplikasi NIP (kecuali untuk user saat ini)
            $existingNip = $this->userRepository->findOneBy(['nip' => $user->getNip()]);
            if ($existingNip && $existingNip->getId() !== $user->getId()) {
                $this->addFlash('error', 'NIP sudah digunakan');
                return $this->render('admin/user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form,
                ]);
            }

            // Cek duplikasi email (kecuali untuk user saat ini)
            $existingEmail = $this->userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingEmail && $existingEmail->getId() !== $user->getId()) {
                $this->addFlash('error', 'Email sudah digunakan');
                return $this->render('admin/user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form,
                ]);
            }

            // Update password jika diisi
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'User berhasil diperbarui');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('delete_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        // Tidak bisa hapus diri sendiri
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Tidak dapat menghapus akun sendiri');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        // Cek apakah masih ada pengajuan cuti yang tidak bisa dihapus
        $activePengajuan = $user->getPengajuanCutis()->filter(function($pengajuan) {
            return in_array($pengajuan->getStatus(), ['diajukan', 'disetujui']);
        });
        
        if (!$activePengajuan->isEmpty()) {
            $this->addFlash('error', 'Tidak dapat menghapus user yang masih memiliki pengajuan cuti aktif (diajukan/disetujui). Batalkan atau proses pengajuan tersebut terlebih dahulu.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        try {
            // Hapus semua hak cuti terlebih dahulu
            foreach ($user->getHakCutis() as $hakCuti) {
                $this->entityManager->remove($hakCuti);
            }

            // Hapus semua pengajuan cuti yang sudah selesai (draft, ditolak, dibatalkan)
            foreach ($user->getPengajuanCutis() as $pengajuan) {
                if (in_array($pengajuan->getStatus(), ['draft', 'ditolak', 'dibatalkan'])) {
                    $this->entityManager->remove($pengajuan);
                }
            }

            // Flush untuk menghapus data terkait terlebih dahulu
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Gagal menghapus data terkait: ' . $e->getMessage());
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $nama = $user->getNama();
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', "User '{$nama}' berhasil dihapus");
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/change-role', name: 'change_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeRole(Request $request, User $user): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('change_role_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        // Tidak bisa ubah role diri sendiri
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Tidak dapat mengubah role akun sendiri');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $newRole = $request->request->get('role');
        $allowedRoles = ['ROLE_USER', 'ROLE_APPROVER', 'ROLE_ADMIN'];

        if (!in_array($newRole, $allowedRoles)) {
            $this->addFlash('error', 'Role tidak valid');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        // Set role baru (tetap include ROLE_USER sebagai base)
        $roles = ['ROLE_USER'];
        if ($newRole !== 'ROLE_USER') {
            $roles[] = $newRole;
        }
        $user->setRoles($roles);

        $this->entityManager->flush();

        $roleNames = [
            'ROLE_USER' => 'User',
            'ROLE_APPROVER' => 'Approver',
            'ROLE_ADMIN' => 'Admin'
        ];

        $this->addFlash('success', "Role '{$user->getNama()}' berhasil diubah menjadi {$roleNames[$newRole]}");
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/reset-password', name: 'reset_password', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resetPassword(Request $request, User $user): Response
    {
        // Validasi CSRF token
        if (!$this->isCsrfTokenValid('reset_password_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token tidak valid');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        // Reset password ke NIP
        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getNip());
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        $this->addFlash('success', "Password '{$user->getNama()}' berhasil direset ke NIP");
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/bulk-import', name: 'bulk_import', methods: ['GET', 'POST'])]
    public function bulkImport(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('csv_file');
            
            if (!$file || $file->getClientOriginalExtension() !== 'csv') {
                $this->addFlash('error', 'File harus berformat CSV');
                return $this->render('admin/user/bulk_import.html.twig');
            }

            $handle = fopen($file->getPathname(), 'r');
            $header = fgetcsv($handle); // Skip header row
            $imported = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    // Format CSV: nip,nama,email,unit_kerja_kode,status_kepegawaian,jenis_kelamin,tmt_cpns
                    [$nip, $nama, $email, $unitKerjaKode, $statusKepegawaian, $jenisKelamin, $tmtCpns] = $row;

                    // Cek duplikasi NIP
                    if ($this->userRepository->findOneBy(['nip' => $nip])) {
                        $errors[] = "NIP {$nip} sudah ada";
                        continue;
                    }

                    // Cari unit kerja
                    $unitKerja = $this->entityManager->getRepository('App:UnitKerja')->findOneBy(['kode' => $unitKerjaKode]);
                    if (!$unitKerja) {
                        $errors[] = "Unit kerja dengan kode {$unitKerjaKode} tidak ditemukan";
                        continue;
                    }

                    $user = new User();
                    $user->setNip($nip);
                    $user->setNama($nama);
                    $user->setEmail($email);
                    $user->setUnitKerja($unitKerja);
                    $user->setStatusKepegawaian($statusKepegawaian);
                    $user->setJenisKelamin($jenisKelamin);
                    $user->setTmtCpns(new \DateTimeImmutable($tmtCpns));
                    $user->setRoles(['ROLE_USER']);

                    // Set password = NIP
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $nip);
                    $user->setPassword($hashedPassword);

                    $this->entityManager->persist($user);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Error pada baris " . ($imported + count($errors) + 2) . ": " . $e->getMessage();
                }
            }

            fclose($handle);
            $this->entityManager->flush();

            $this->addFlash('success', "{$imported} user berhasil diimport");
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('warning', $error);
                }
            }

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/bulk_import.html.twig');
    }
}