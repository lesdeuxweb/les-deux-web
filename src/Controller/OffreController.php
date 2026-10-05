<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Repository\OffreRepository;
use App\Repository\OptionTarifaireRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/offres')]
class OffreController extends AbstractController
{
    #[Route('', name: 'app_offre_index', methods: ['GET'])]
    public function index(OffreRepository $offreRepository, OptionTarifaireRepository $optionRepository): Response
    {
        return $this->render('offre/index.html.twig', [
            'creations' => $offreRepository->findPublieesParCategorie(Offre::CATEGORIE_CREATION),
            'abonnements' => $offreRepository->findPublieesParCategorie(Offre::CATEGORIE_ABONNEMENT),
            'options' => $optionRepository->findPublie(),
        ]);
    }

    #[Route('/{slug}', name: 'app_offre_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOnePublieBySlug(slug)')] Offre $offre,
        OffreRepository $offreRepository,
    ): Response {
        return $this->render('offre/show.html.twig', [
            'offre' => $offre,
            'autres_offres' => array_filter($offreRepository->findPublie(), fn (Offre $o) => $o !== $offre),
        ]);
    }
}
