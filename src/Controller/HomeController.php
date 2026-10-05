<?php

namespace App\Controller;

use App\Repository\OffreRepository;
use App\Repository\RealisationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(OffreRepository $offreRepository, RealisationRepository $realisationRepository): Response
    {
        // Secteurs et zones : fournis par les fonctions Twig secteurs_publies() / zones_publiees().
        return $this->render('home/index.html.twig', [
            'offres' => $offreRepository->findPublie(),
            'realisations' => $realisationRepository->findMisesEnAvant(3),
        ]);
    }
}
