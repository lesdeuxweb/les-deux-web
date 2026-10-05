<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * robots.txt généré dynamiquement : l'adresse du sitemap suit le nom de domaine réel.
 */
class RobotsController
{
    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'], format: 'txt')]
    public function __invoke(UrlGeneratorInterface $urlGenerator): Response
    {
        $sitemap = $urlGenerator->generate('PrestaSitemapBundle_index', ['_format' => 'xml'], UrlGeneratorInterface::ABSOLUTE_URL);

        $contenu = <<<TXT
            User-agent: *
            Disallow: /admin

            Sitemap: {$sitemap}

            TXT;

        $reponse = new Response($contenu, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
        $reponse->setPublic()->setMaxAge(86400);

        return $reponse;
    }
}
