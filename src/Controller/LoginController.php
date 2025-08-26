<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(LoginType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Cari user berdasarkan NIP
            $existingUser = $userRepository->findByNip($user->getNip());
            
            if ($existingUser && $existingUser->getPassword() === $user->getPassword()) {
                // Login berhasil - dalam implementasi nyata, gunakan password hashing
                $this->addFlash('success', 'Login berhasil! Selamat datang, ' . $existingUser->getNama());
                
                // Redirect ke dashboard atau halaman utama
                return $this->redirectToRoute('app_dashboard');
            } else {
                // Login gagal
                $this->addFlash('error', 'NIP atau password salah!');
            }
        }

        return $this->render('login/login.html.twig', [
            'form' => $form,
            'title' => 'Login Aplikasi Cuti - Kanwil Kemenag Sulbar'
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('login/dashboard.html.twig', [
            'title' => 'Dashboard Aplikasi Cuti'
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Dalam implementasi nyata, ini akan di-handle oleh security system
        $this->addFlash('info', 'Anda telah logout');
        return $this->redirectToRoute('app_login');
    }
}