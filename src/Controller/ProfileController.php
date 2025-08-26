<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'profile_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();

                $this->addFlash('success', 'Profil berhasil diperbarui.');

                return $this->redirectToRoute('profile_show');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/change-password', name: 'profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $form->get('currentPassword')->addError(new \Symfony\Component\Form\FormError('Password saat ini tidak benar.'));
            } else {
                try {
                    // Hash and set new password
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
                    $user->setPassword($hashedPassword);
                    
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Password berhasil diubah.');

                    return $this->redirectToRoute('profile_show');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Gagal mengubah password: ' . $e->getMessage());
                }
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}