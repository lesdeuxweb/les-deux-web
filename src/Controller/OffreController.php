<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreController extends AbstractController
{
    // Page provisoire (phase 2) : contenu réel en phase 3.
    #[Route('/offres', name: 'app_offre_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('page/provisoire.html.twig', ['cle_titre' => 'nav.offres']);
    }
}
