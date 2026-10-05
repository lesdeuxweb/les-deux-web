<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Connexion au back-office. Le traitement du formulaire est assuré par le pare-feu (form_login).
 */
class SecurityController extends AbstractController
{
    #[Route('/admin/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin');
        }

        return $this->render('security/login.html.twig', [
            'dernier_email' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Intercepté par le pare-feu (security.yaml > logout).');
    }
}
