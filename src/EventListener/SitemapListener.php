<?php

namespace App\EventListener;

use App\Repository\OffreRepository;
use App\Repository\RealisationRepository;
use App\Repository\SecteurRepository;
use App\Repository\ZoneRepository;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contenu du sitemap.xml : pages fixes + contenus publiés (avec date de dernière modification).
 * Les brouillons et le back-office n'y figurent jamais.
 *
 * /sitemap.xml est un index qui pointe vers /sitemap.pages.xml et /sitemap.contenus.xml.
 */
#[AsEventListener(event: SitemapPopulateEvent::class)]
class SitemapListener
{
    /** Pages fixes et leur priorité relative. */
    private const PAGES = [
        'app_home' => 1.0,
        'app_offre_index' => 0.9,
        'app_realisation_index' => 0.8,
        'app_zone_index' => 0.8,
        'app_contact' => 0.8,
        'app_about' => 0.6,
        'app_legal' => 0.2,
        'app_privacy' => 0.2,
    ];

    public function __construct(
        private readonly OffreRepository $offreRepository,
        private readonly RealisationRepository $realisationRepository,
        private readonly SecteurRepository $secteurRepository,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    public function __invoke(SitemapPopulateEvent $event): void
    {
        $section = $event->getSection();
        $urls = $event->getUrlContainer();
        $generateur = $event->getUrlGenerator();

        if (\in_array($section, [null, 'pages'], true)) {
            foreach (self::PAGES as $route => $priorite) {
                $urls->addUrl(new UrlConcrete($this->url($generateur, $route), priority: $priorite), 'pages');
            }
        }

        if (\in_array($section, [null, 'contenus'], true)) {
            $contenus = [
                'app_offre_show' => [$this->offreRepository->findPublie(), 0.8],
                'app_secteur_show' => [$this->secteurRepository->findPublie(), 0.8],
                'app_zone_show' => [$this->zoneRepository->findPublie(), 0.8],
                'app_realisation_show' => [$this->realisationRepository->findPubliees(), 0.6],
            ];

            foreach ($contenus as $route => [$entites, $priorite]) {
                foreach ($entites as $entite) {
                    $urls->addUrl(new UrlConcrete(
                        $this->url($generateur, $route, ['slug' => $entite->getSlug()]),
                        $entite->getUpdatedAt() ?? $entite->getCreatedAt(),
                        priority: $priorite,
                    ), 'contenus');
                }
            }
        }
    }

    /** @param array<string, string> $parametres */
    private function url(UrlGeneratorInterface $generateur, string $route, array $parametres = []): string
    {
        return $generateur->generate($route, $parametres, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
