<?php

namespace App\Tests\Controller;

use App\Entity\Offre;
use App\Tests\Fabrique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Site en une page : sections de l'accueil, grille tarifaire, liens internes.
 */
class AccueilTest extends WebTestCase
{
    private KernelBrowser $client;
    private Fabrique $fabrique;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->fabrique = new Fabrique(static::getContainer()->get(EntityManagerInterface::class));
    }

    public function testSectionsDeLaMaquette(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Votre métier mérite un site qui vous ressemble.');
        foreach (['offres', 'qui-sommes-nous', 'contact'] as $ancre) {
            self::assertCount(1, $crawler->filter('#'.$ancre), sprintf('Section #%s absente.', $ancre));
        }
        foreach (['Le web, simplement', 'Notre façon de faire', 'Ici et ailleurs', 'Parlons-nous'] as $surTitre) {
            self::assertStringContainsString(mb_strtoupper($surTitre), mb_strtoupper($crawler->filter('main')->text()));
        }
        self::assertCount(4, $crawler->filter('.etape'));
        self::assertSelectorTextContains('.hero__zone', "100\u{A0}km"); // espace insécable
    }

    public function testMenuVersLesSections(): void
    {
        $crawler = $this->client->request('GET', '/mentions-legales');

        $liens = $crawler->filter('.nav-principale a')->each(fn (Crawler $a) => $a->attr('href'));
        self::assertSame(['/#offres', '/#qui-sommes-nous', '/#contact'], $liens);
    }

    public function testCartesPuisGrilleComplete(): void
    {
        $this->fabrique->offre('L’Essentiel', prix: 199)->setEnCarte(true);
        $this->fabrique->offre('La Vitrine', prix: 399)->setEnCarte(true)->setBadge('Notre conseil');
        $this->fabrique->offre('Site sur mesure', prix: 900)->setEnCarte(true)->setAPartirDe(true);
        $this->fabrique->offre('Site 3 pages', prix: 299);
        $this->fabrique->offre('Pack Évolution', prix: 35, categorie: Offre::CATEGORIE_ABONNEMENT)->setPrixSuffixe('/ mois');
        $this->fabrique->offre('Brouillon', publie: false)->setEnCarte(true);
        $this->fabrique->option('Page supplémentaire', '50 €');
        $this->fabrique->option('Option masquée', '1 €', publie: false);
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/');
        $offres = $crawler->filter('#offres');

        // Trois cartes, numérotées, avec libellé de prix
        $cartes = $offres->filter('.grille-offres')->first()->filter('.carte-offre');
        self::assertCount(3, $cartes);
        self::assertSame(['01', '02', '03'], $cartes->filter('.carte-offre__numero')->each(fn (Crawler $n) => $n->text()));
        $prix = $cartes->filter('.prix')->each(fn (Crawler $n) => trim(preg_replace('/\s+/u', ' ', $n->text())));
        self::assertSame(['Prix de lancement 199 €', 'Prix de lancement 399 €', 'À partir de 900 €'], $prix);
        self::assertSelectorTextContains('.carte-offre--vedette .carte-offre__badge', 'Notre conseil');

        // Grille complète repliée par défaut (<details> sans attribut open)
        $grille = $offres->filter('details.grille-complete');
        self::assertCount(1, $grille);
        self::assertNull($grille->attr('open'));
        self::assertStringContainsString('Voir toutes les offres', $grille->filter('summary')->text());

        // Autres offres de création, options, abonnements
        $lignes = $grille->filter('.tableau-offres tbody tr')->each(fn (Crawler $tr) => [$tr->filter('th')->text(), $tr->filter('.tableau-offres__prix')->text()]);
        self::assertSame([['Site 3 pages', '299 €']], $lignes);

        // Abonnements : cartes dans la grille complète
        $pack = $grille->filter('.abonnements .carte-offre');
        self::assertCount(1, $pack);
        self::assertStringContainsString('Pack Évolution', $pack->text());
        self::assertSame('Abonnement 35 € / mois', trim(preg_replace('/\s+/u', ' ', $pack->filter('.prix')->text())));
        self::assertSame(['Page supplémentaire', '50 €'], $grille->filter('.options-liste li')->first()->children()->each(fn (Crawler $n) => $n->text()));

        // Brouillons masqués, pas de « HT », mention de TVA
        self::assertStringNotContainsString('Brouillon', $offres->text());
        self::assertStringNotContainsString('Option masquée', $offres->text());
        self::assertStringNotContainsString(' HT', $offres->text());
        self::assertStringContainsString('TVA non applicable, art. 293 B du CGI', $offres->text());
    }

    public function testCarteOffreMeneAuFormulaireAvecLOffre(): void
    {
        $this->fabrique->offre('Site 5 pages')->setEnCarte(true);
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/');
        $lien = $crawler->filter('#offres .carte__lien')->attr('href');
        self::assertSame('/?offre=site-5-pages#contact', $lien);

        $crawler = $this->client->request('GET', '/?offre=site-5-pages');
        self::assertSame('site-5-pages', $crawler->filter('#contact_offre option[selected]')->attr('value'));
    }

    public function testAncienneAdresseContactRedirigeVersLeFormulaire(): void
    {
        $this->client->request('GET', '/contact?offre=site-5-pages');

        self::assertResponseStatusCodeSame(301);
        self::assertResponseRedirects('/?offre=site-5-pages#contact');
    }

    public function testPagesSupprimees(): void
    {
        foreach (['/offres', '/realisations', '/zones-d-intervention', '/qui-sommes-nous', '/site-internet-pour/artisans'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(404, $url);
        }
    }
}
