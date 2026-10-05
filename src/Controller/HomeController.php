<?php

namespace App\Controller;

use App\Entity\MessageContact;
use App\Form\ContactType;
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
            'offres' => $offreRepository->findSurAccueil(),
            'realisations' => $realisationRepository->findMisesEnAvant(3),
            // Formulaire de contact intégré en bas de page, envoyé vers /contact
            'form' => $this->createForm(ContactType::class, new MessageContact()),
        ]);
    }
}
