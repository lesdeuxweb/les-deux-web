<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Layout commun : structure accessible, fil d'Ariane, aucune ressource externe, aucun cookie.
 */
class LayoutTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /** @return iterable<string, array{string}> */
    public static function pagesPubliques(): iterable
    {
        yield 'accueil' => ['/'];
        yield 'mentions légales' => ['/mentions-legales'];
        yield 'confidentialité' => ['/confidentialite'];
    }

    #[DataProvider('pagesPubliques')]
    public function testStructureCommune(string $url): void
    {
        $crawler = $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="fr"]');
        self::assertCount(1, $crawler->filter('h1'), 'Un seul <h1> par page.');
        self::assertNotEmpty(trim($crawler->filter('title')->text()));
        self::assertNotEmpty($crawler->filter('meta[name="description"]')->attr('content'));

        // Lien d'évitement en premier élément focusable, vers le contenu principal
        $evitement = $crawler->filter('body a')->first();
        self::assertSame('#contenu', $evitement->attr('href'));
        self::assertSelectorExists('main#contenu');

        self::assertSelectorExists('nav[aria-label="Navigation principale"]');
        self::assertSelectorExists('footer');
    }

    #[DataProvider('pagesPubliques')]
    public function testAucuneRessourceExterne(string $url): void
    {
        $crawler = $this->client->request('GET', $url);

        // Ressources réellement chargées par le navigateur (les liens canonical / og:url ne le sont pas)
        $ressources = $crawler->filter('link[rel="stylesheet"], link[rel="icon"], link[rel="apple-touch-icon"], link[rel="preload"], link[rel="modulepreload"], script[src], img[src], iframe[src], source[src]')
            ->each(fn (Crawler $n) => $n->attr('href') ?? $n->attr('src'));
        self::assertNotEmpty($ressources);

        foreach ($ressources as $ressource) {
            self::assertMatchesRegularExpression('#^(/(?!/)|http://localhost/)#', $ressource, sprintf('Ressource externe chargée : %s', $ressource));
        }

        // Les imports JavaScript (importmap) restent eux aussi locaux
        $importmap = $crawler->filter('script[type="importmap"]');
        if ($importmap->count()) {
            foreach (json_decode($importmap->text(), true)['imports'] as $module) {
                self::assertStringStartsWith('/', $module);
            }
        }
    }

    #[DataProvider('pagesPubliques')]
    public function testAucunRepereACompleter(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertStringNotContainsString('COMPLÉTER', $this->client->getResponse()->getContent());
    }

    #[DataProvider('pagesPubliques')]
    public function testAucunCookieDepose(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertSame([], $this->client->getResponse()->headers->getCookies(), 'Les pages publiques ne doivent déposer aucun cookie.');
    }

    public function testPasDeFilDArianeSurLAccueil(): void
    {
        $this->client->request('GET', '/');

        self::assertSelectorNotExists('nav.fil-ariane');
    }

    public function testFilDArianeDesPagesLegales(): void
    {
        $crawler = $this->client->request('GET', '/mentions-legales');

        $fil = $crawler->filter('nav.fil-ariane li');
        self::assertCount(2, $fil);
        self::assertSame('/', $fil->eq(0)->filter('a')->attr('href'));
        self::assertSame('Mentions légales', $fil->eq(1)->filter('[aria-current="page"]')->text());
    }

    public function testPolicesAutoHebergees(): void
    {
        $crawler = $this->client->request('GET', '/');

        $polices = $crawler->filter('link[rel="preload"][as="font"]')->each(fn (Crawler $n) => $n->attr('href'));
        self::assertNotEmpty($polices);
        foreach ($polices as $police) {
            self::assertStringStartsWith('/assets/fonts/', $police);
        }
    }
}
