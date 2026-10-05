<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Repository\OffreRepository;
use App\Repository\RealisationRepository;
use App\Repository\ZoneRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Zones d'intervention en présentiel : porte d'entrée SEO locale. */
class ZoneController extends AbstractController
{
    #[Route('/zones-d-intervention', name: 'app_zone_index', methods: ['GET'])]
    public function index(ZoneRepository $zoneRepository): Response
    {
        $zones = $zoneRepository->findPublie();

        return $this->render('zone/index.html.twig', [
            'departements' => array_filter($zones, fn (Zone $z) => Zone::TYPE_DEPARTEMENT === $z->getType()),
            'villes' => array_filter($zones, fn (Zone $z) => Zone::TYPE_VILLE === $z->getType()),
        ]);
    }

    #[Route('/creation-site-internet/{slug}', name: 'app_zone_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOnePublieBySlug(slug)')] Zone $zone,
        RealisationRepository $realisationRepository,
        OffreRepository $offreRepository,
        ZoneRepository $zoneRepository,
    ): Response {
        return $this->render('zone/show.html.twig', [
            'zone' => $zone,
            'realisations' => $realisationRepository->findPubliees(zone: $zone, limit: 6),
            'offres' => $offreRepository->findPublie(),
            'autres_zones' => array_filter($zoneRepository->findPublie(), fn (Zone $z) => $z !== $zone),
        ]);
    }
}
