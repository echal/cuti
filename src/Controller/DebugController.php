<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/user', name: 'debug_user')]
    public function debugUser(): Response
    {
        $user = $this->getUser();
        
        $data = [
            'is_logged_in' => $user !== null,
            'user_data' => $user ? [
                'nip' => $user->getNip(),
                'nama' => $user->getNama(),
                'roles' => $user->getRoles(),
            ] : null,
            'session_id' => session_id(),
        ];
        
        return $this->json($data);
    }
    
    #[Route('/debug/clear-session', name: 'debug_clear_session')]
    public function clearSession(): Response
    {
        session_destroy();
        setcookie('REMEMBERME', '', time() - 3600, '/');
        
        return $this->json(['message' => 'Session cleared, redirecting to login']);
    }
}