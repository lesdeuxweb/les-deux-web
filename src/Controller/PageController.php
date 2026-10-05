<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages au contenu fixe : qui sommes-nous, mentions légales, confidentialité.
 */
class PageController extends AbstractController
{
    #[Route('/qui-sommes-nous', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    // Pages provisoires : contenu réel en phase 7 (légal et RGPD).
    #[Route('/mentions-legales', name: 'app_legal', methods: ['GET'])]
    public function legal(): Response
    {
        return $this->render('page/provisoire.html.twig', ['cle_titre' => 'pied.mentions_legales']);
    }

    #[Route('/confidentialite', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('page/provisoire.html.twig', ['cle_titre' => 'pied.confidentialite']);
    }
}
