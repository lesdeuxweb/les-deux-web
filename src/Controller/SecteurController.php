<?php

namespace App\Controller;

use App\Entity\Secteur;
use App\Repository\OffreRepository;
use App\Repository\RealisationRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Pages métier (« Site internet pour gîtes et chambres d'hôtes ») : porte d'entrée SEO nationale. */
class SecteurController extends AbstractController
{
    #[Route('/site-internet-pour/{slug}', name: 'app_secteur_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOnePublieBySlug(slug)')] Secteur $secteur,
        RealisationRepository $realisationRepository,
        OffreRepository $offreRepository,
    ): Response {
        return $this->render('secteur/show.html.twig', [
            'secteur' => $secteur,
            'realisations' => $realisationRepository->findPubliees(secteur: $secteur, limit: 6),
            'offres' => $offreRepository->findPublie(),
        ]);
    }
}
