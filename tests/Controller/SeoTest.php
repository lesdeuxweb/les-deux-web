<?php

namespace App\Tests\Controller;

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
    private const PAGES = ['/', '/mentions-legales', '/confidentialite'];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Accueil avec des offres et des options, pour vérifier la hiérarchie des titres en situation réelle
        $fabrique = new Fabrique(static::getContainer()->get(EntityManagerInterface::class));
        $fabrique->offre('Site 5 pages');
        $fabrique->offre('Pack Évolution', categorie: 'abonnement');
        $fabrique->option('Page supplémentaire', '50 €');
        $fabrique->flush();
    }

    public function testMetasCanonicalEtOpenGraph(): void
    {
        foreach (self::PAGES as $url) {
            $crawler = $this->client->request('GET', $url);

            $titre = $crawler->filter('title')->text();
            $description = $crawler->filter('meta[name="description"]')->attr('content');
            self::assertStringContainsString('Les deux web', $titre, $url);
            self::assertNotEmpty($description, $url);
            self::assertLessThanOrEqual(160, mb_strlen($description), $url);

            $canonical = $crawler->filter('link[rel="canonical"]')->attr('href');
            self::assertSame('http://localhost'.$url, $canonical);
            self::assertSame($canonical, $crawler->filter('meta[property="og:url"]')->attr('content'), $url);

            foreach (['og:type', 'og:title', 'og:description', 'og:image', 'og:site_name', 'og:locale'] as $propriete) {
                self::assertNotEmpty($crawler->filter(sprintf('meta[property="%s"]', $propriete))->attr('content'), sprintf('%s absent sur %s', $propriete, $url));
            }
            self::assertSelectorNotExists('meta[name="robots"]');
        }
    }

    public function testCanonicalSansParametres(): void
    {
        $crawler = $this->client->request('GET', '/?offre=site-5-pages&utm_source=facebook');

        self::assertSame('http://localhost/', $crawler->filter('link[rel="canonical"]')->attr('href'));
    }

    public function testJsonLdEntreprise(): void
    {
        $entreprise = $this->jsonLd($this->client->request('GET', '/'), 'ProfessionalService');

        self::assertSame('Les deux web', $entreprise['name']);
        self::assertSame('http://localhost/', $entreprise['url']);
        self::assertSame('Limoges', $entreprise['address']['addressLocality']);
        self::assertSame('bonjour@lesdeuxweb.fr', $entreprise['email']);
        self::assertContains(['@type' => 'City', 'name' => 'Limoges'], $entreprise['areaServed']);
        self::assertContains(['@type' => 'AdministrativeArea', 'name' => 'Dordogne'], $entreprise['areaServed']);
        self::assertContains(['@type' => 'Country', 'name' => 'France'], $entreprise['areaServed']);

        // Les valeurs « [À COMPLÉTER] » ne sont jamais publiées dans les données structurées
        self::assertStringNotContainsString('COMPLÉTER', json_encode($entreprise, \JSON_UNESCAPED_UNICODE));
    }

    public function testJsonLdFilDArianeSurLesPagesLegales(): void
    {
        $fil = $this->jsonLd($this->client->request('GET', '/mentions-legales'), 'BreadcrumbList');

        self::assertSame(
            [[1, 'Accueil', 'http://localhost/'], [2, 'Mentions légales', 'http://localhost/mentions-legales']],
            array_map(fn (array $e) => [$e['position'], $e['name'], $e['item']], $fil['itemListElement']),
        );

        // Pas de fil d'Ariane sur l'accueil
        self::assertNull($this->jsonLd($this->client->request('GET', '/'), 'BreadcrumbList'));
    }

    public function testSitemap(): void
    {
        $this->client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<loc>http://localhost/sitemap.pages.xml</loc>', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/sitemap.pages.xml');
        $xml = new \SimpleXMLElement($this->client->getResponse()->getContent());
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }

        self::assertSame(['http://localhost/', 'http://localhost/mentions-legales', 'http://localhost/confidentialite'], $urls);
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
        foreach (self::PAGES as $url) {
            $niveaux = $this->client->request('GET', $url)
                ->filter('h1, h2, h3, h4, h5, h6')
                ->each(fn (Crawler $titre) => (int) substr($titre->nodeName(), 1));

            self::assertSame(1, $niveaux[0], sprintf('Le premier titre de %s doit être le <h1>.', $url));
            self::assertCount(1, array_keys($niveaux, 1), sprintf('Un seul <h1> sur %s.', $url));
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
