<?php

namespace App\EventListener;

use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contenu du sitemap.xml : le site tient en une page (accueil) + les pages légales.
 * /sitemap.xml est un index qui pointe vers /sitemap.pages.xml.
 */
#[AsEventListener(event: SitemapPopulateEvent::class)]
class SitemapListener
{
    /** Pages publiques et leur priorité relative. */
    private const PAGES = [
        'app_home' => 1.0,
        'app_legal' => 0.2,
        'app_privacy' => 0.2,
    ];

    public function __invoke(SitemapPopulateEvent $event): void
    {
        if (!\in_array($event->getSection(), [null, 'pages'], true)) {
            return;
        }

        foreach (self::PAGES as $route => $priorite) {
            $event->getUrlContainer()->addUrl(new UrlConcrete(
                $event->getUrlGenerator()->generate($route, [], UrlGeneratorInterface::ABSOLUTE_URL),
                priority: $priorite,
            ), 'pages');
        }
    }
}
