<?php

namespace App\Controller;

use App\Entity\Realisation;
use App\Repository\RealisationRepository;
use App\Repository\SecteurRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/realisations')]
class RealisationController extends AbstractController
{
    /** Liste filtrable par secteur côté serveur : /realisations?secteur={slug}. */
    #[Route('', name: 'app_realisation_index', methods: ['GET'])]
    public function index(
        RealisationRepository $realisationRepository,
        SecteurRepository $secteurRepository,
        #[MapQueryParameter] ?string $secteur = null,
    ): Response {
        $secteurActif = null;
        if (null !== $secteur && '' !== $secteur) {
            $secteurActif = $secteurRepository->findOnePublieBySlug($secteur)
                ?? throw $this->createNotFoundException('Secteur inconnu.');
        }

        return $this->render('realisation/index.html.twig', [
            'realisations' => $realisationRepository->findPubliees($secteurActif),
            'secteurs' => $secteurRepository->findPubliesAvecRealisations(),
            'secteur_actif' => $secteurActif,
        ]);
    }

    #[Route('/{slug}', name: 'app_realisation_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOnePublieeBySlug(slug)')] Realisation $realisation,
    ): Response {
        return $this->render('realisation/show.html.twig', [
            'realisation' => $realisation,
        ]);
    }
}
