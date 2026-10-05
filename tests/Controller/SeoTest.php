<?php

namespace App\Tests\Controller;

use App\Entity\Zone;
use App\Tests\Fabrique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * SEO technique : metas, canonical, Open Graph, JSON-LD, sitemap, robots.txt, hiérarchie des titres.
 */
class SeoTest extends WebTestCase
{
    private KernelBrowser $client;
    private Fabrique $fabrique;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->fabrique = new Fabrique(static::getContainer()->get(EntityManagerInterface::class));
    }

    /** Toutes les pages publiques, avec un jeu de contenus minimal. @return list<string> */
    private function creerContenusEtListerPages(): array
    {
        $secteur = $this->fabrique->secteur('Artisans', libelleCible: 'artisans du bâtiment');
        $zone = $this->fabrique->zone('Limoges', Zone::TYPE_VILLE);
        $this->fabrique->offre('Site vitrine');
        $this->fabrique->realisation('Menuiserie', $secteur, $zone, misEnAvant: true);
        $this->fabrique->flush();

        return [
            '/', '/offres', '/offres/site-vitrine', '/realisations', '/realisations?secteur=artisans',
            '/realisations/menuiserie', '/site-internet-pour/artisans', '/zones-d-intervention',
            '/creation-site-internet/limoges', '/qui-sommes-nous', '/contact', '/mentions-legales', '/confidentialite',
        ];
    }

    public function testMetasCanonicalEtOpenGraphSurToutesLesPages(): void
    {
        foreach ($this->creerContenusEtListerPages() as $url) {
            $crawler = $this->client->request('GET', $url);

            $titre = $crawler->filter('title')->text();
            $description = $crawler->filter('meta[name="description"]')->attr('content');
            self::assertNotEmpty($titre, $url);
            self::assertStringContainsString('Les deux web', $titre, $url);
            self::assertNotEmpty($description, $url);
            self::assertLessThanOrEqual(160, mb_strlen($description), $url);

            $canonical = $crawler->filter('link[rel="canonical"]')->attr('href');
            self::assertStringStartsWith('http://localhost/', $canonical, $url);
            self::assertSame($canonical, $crawler->filter('meta[property="og:url"]')->attr('content'), $url);

            foreach (['og:type', 'og:title', 'og:description', 'og:image', 'og:site_name', 'og:locale'] as $propriete) {
                self::assertNotEmpty($crawler->filter(sprintf('meta[property="%s"]', $propriete))->attr('content'), sprintf('%s absent sur %s', $propriete, $url));
            }
            self::assertStringStartsWith('http://localhost/', $crawler->filter('meta[property="og:image"]')->attr('content'));
            self::assertSelectorNotExists('meta[name="robots"]');
        }
    }

    public function testCanonicalSansParametresDeSuivi(): void
    {
        $this->fabrique->offre('Site vitrine');
        $this->fabrique->secteur('Artisans');
        $this->fabrique->realisation('Projet', $this->fabrique->secteur('Communes'));
        $this->fabrique->flush();

        $canonical = fn (string $url) => $this->client->request('GET', $url)->filter('link[rel="canonical"]')->attr('href');

        self::assertSame('http://localhost/contact', $canonical('/contact?offre=site-vitrine'));
        self::assertSame('http://localhost/offres/site-vitrine', $canonical('/offres/site-vitrine?utm_source=facebook'));
        // Le filtre par secteur, lui, fait partie de l'URL canonique
        self::assertSame('http://localhost/realisations?secteur=communes', $canonical('/realisations?secteur=communes&utm_source=x'));
    }

    public function testMetaTitleEtDescriptionPersonnalisesPrioritaires(): void
    {
        $this->fabrique->offre('Site vitrine')
            ->setMetaTitle('Création de site vitrine à Limoges')
            ->setMetaDescription('Description saisie dans le back-office.');
        $this->fabrique->offre('Boutique')->setAccroche(str_repeat('Une accroche très longue pour tester la coupe. ', 5));
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/offres/site-vitrine');
        self::assertSame('Création de site vitrine à Limoges', $crawler->filter('title')->text());
        self::assertSame('Description saisie dans le back-office.', $crawler->filter('meta[name="description"]')->attr('content'));

        $description = $this->client->request('GET', '/offres/boutique')->filter('meta[name="description"]')->attr('content');
        self::assertLessThanOrEqual(160, mb_strlen($description));
        self::assertStringEndsWith('…', $description);
    }

    public function testJsonLdEntreprise(): void
    {
        $this->fabrique->zone('Dordogne');
        $this->fabrique->zone('Limoges', Zone::TYPE_VILLE);
        $this->fabrique->zone('Brouillon', publie: false);
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/');
        $entreprise = $this->jsonLd($crawler, 'ProfessionalService');

        self::assertSame('Les deux web', $entreprise['name']);
        self::assertSame('http://localhost/', $entreprise['url']);
        self::assertSame('Limoges', $entreprise['address']['addressLocality']);
        self::assertSame('FR', $entreprise['address']['addressCountry']);
        self::assertContains(['@type' => 'AdministrativeArea', 'name' => 'Dordogne'], $entreprise['areaServed']);
        self::assertContains(['@type' => 'City', 'name' => 'Limoges'], $entreprise['areaServed']);
        self::assertContains(['@type' => 'Country', 'name' => 'France'], $entreprise['areaServed']);
        self::assertNotContains(['@type' => 'AdministrativeArea', 'name' => 'Brouillon'], $entreprise['areaServed']);

        // Les valeurs « [À COMPLÉTER] » ne sont jamais publiées dans les données structurées
        self::assertStringNotContainsString('COMPLÉTER', json_encode($entreprise, \JSON_UNESCAPED_UNICODE));
    }

    public function testJsonLdFilDAriane(): void
    {
        $this->fabrique->zone('Charente');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/creation-site-internet/charente');
        $fil = $this->jsonLd($crawler, 'BreadcrumbList');

        self::assertSame(
            [
                [1, 'Accueil', 'http://localhost/'],
                [2, 'Zones d\'intervention', 'http://localhost/zones-d-intervention'],
                [3, 'Charente', 'http://localhost/creation-site-internet/charente'],
            ],
            array_map(fn (array $e) => [$e['position'], $e['name'], $e['item']], $fil['itemListElement']),
        );

        // Pas de fil d'Ariane sur l'accueil
        self::assertNull($this->jsonLd($this->client->request('GET', '/'), 'BreadcrumbList'));
    }

    public function testImageDePartageDeLaRealisation(): void
    {
        $this->fabrique->realisation('Projet')->setImageName('capture.webp');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/realisations/projet');

        self::assertSame('http://localhost/uploads/realisations/capture.webp', $crawler->filter('meta[property="og:image"]')->attr('content'));
        self::assertSame('article', $crawler->filter('meta[property="og:type"]')->attr('content'));
    }

    public function testSitemap(): void
    {
        $this->fabrique->offre('Site vitrine');
        $this->fabrique->offre('Offre brouillon', publie: false);
        $this->fabrique->secteur('Artisans');
        $this->fabrique->zone('Dordogne');
        $this->fabrique->realisation('Menuiserie');
        $this->fabrique->realisation('Projet brouillon', publie: false);
        $this->fabrique->flush();

        $this->client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        $index = $this->client->getResponse()->getContent();
        self::assertStringContainsString('<loc>http://localhost/sitemap.pages.xml</loc>', $index);
        self::assertStringContainsString('<loc>http://localhost/sitemap.contenus.xml</loc>', $index);

        $this->client->request('GET', '/sitemap.pages.xml');
        $pages = $this->client->getResponse()->getContent();
        foreach (['/', '/offres', '/realisations', '/zones-d-intervention', '/qui-sommes-nous', '/contact', '/mentions-legales', '/confidentialite'] as $chemin) {
            self::assertStringContainsString('<loc>http://localhost'.$chemin.'</loc>', $pages);
        }
        self::assertStringNotContainsString('/admin', $pages);

        $this->client->request('GET', '/sitemap.contenus.xml');
        $xml = new \SimpleXMLElement($this->client->getResponse()->getContent());
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[(string) $url->loc] = (string) $url->lastmod;
        }

        foreach (['/offres/site-vitrine', '/site-internet-pour/artisans', '/creation-site-internet/dordogne', '/realisations/menuiserie'] as $chemin) {
            self::assertArrayHasKey('http://localhost'.$chemin, $urls);
            self::assertNotEmpty($urls['http://localhost'.$chemin], sprintf('lastmod absent pour %s', $chemin));
        }
        self::assertArrayNotHasKey('http://localhost/offres/offre-brouillon', $urls);
        self::assertArrayNotHasKey('http://localhost/realisations/projet-brouillon', $urls);
    }

    public function testRobotsTxt(): void
    {
        $this->client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');
        $contenu = $this->client->getResponse()->getContent();
        self::assertStringContainsString("User-agent: *\nDisallow: /admin\n", $contenu);
        self::assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $contenu);
    }

    public function testHierarchieDesTitresSansNiveauSaute(): void
    {
        foreach ($this->creerContenusEtListerPages() as $url) {
            $niveaux = $this->client->request('GET', $url)
                ->filter('h1, h2, h3, h4, h5, h6')
                ->each(fn (Crawler $titre) => (int) substr($titre->nodeName(), 1));

            self::assertSame(1, $niveaux[0], sprintf('Le premier titre de %s doit être le <h1>.', $url));
            foreach ($niveaux as $i => $niveau) {
                if ($i > 0) {
                    self::assertLessThanOrEqual($niveaux[$i - 1] + 1, $niveau, sprintf('Niveau de titre sauté sur %s : h%d puis h%d.', $url, $niveaux[$i - 1], $niveau));
                }
            }
        }
    }

    /** @return array<string, mixed>|null */
    private function jsonLd(Crawler $crawler, string $type): ?array
    {
        foreach ($crawler->filter('script[type="application/ld+json"]') as $script) {
            $donnees = json_decode($script->textContent, true, flags: \JSON_THROW_ON_ERROR);
            if ($type === ($donnees['@type'] ?? null)) {
                return $donnees;
            }
        }

        return null;
    }
}
