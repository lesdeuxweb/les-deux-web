<?php

namespace App\Tests\Controller;

use App\Entity\Zone;
use App\Tests\Fabrique;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Pages publiques alimentées par la base : affichage, 404, filtres, titres SEO.
 */
class PagesPubliquesTest extends WebTestCase
{
    private KernelBrowser $client;
    private Fabrique $fabrique;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->fabrique = new Fabrique(static::getContainer()->get(EntityManagerInterface::class));
    }

    public function testToutesLesPagesRepondentAvecDuContenu(): void
    {
        $secteur = $this->fabrique->secteur('Artisans');
        $zone = $this->fabrique->zone('Dordogne');
        $this->fabrique->offre('Site vitrine');
        $this->fabrique->realisation('Menuiserie Test', $secteur, $zone, misEnAvant: true);
        $this->fabrique->flush();

        foreach ([
            '/',
            '/offres',
            '/offres/site-vitrine',
            '/realisations',
            '/realisations?secteur=artisans',
            '/realisations/menuiserie-test',
            '/site-internet-pour/artisans',
            '/zones-d-intervention',
            '/creation-site-internet/dordogne',
            '/qui-sommes-nous',
        ] as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
            self::assertCount(1, $crawler->filter('h1'), sprintf('Un seul <h1> sur %s.', $url));
        }
    }

    /** @return iterable<string, array{string}> */
    public static function slugsInconnus(): iterable
    {
        yield 'offre' => ['/offres/inconnu'];
        yield 'réalisation' => ['/realisations/inconnu'];
        yield 'secteur' => ['/site-internet-pour/inconnu'];
        yield 'zone' => ['/creation-site-internet/inconnu'];
        yield 'filtre secteur' => ['/realisations?secteur=inconnu'];
        yield 'slug avec majuscules' => ['/offres/Site-Vitrine'];
    }

    #[DataProvider('slugsInconnus')]
    public function testSlugInconnuRenvoie404(string $url): void
    {
        $this->fabrique->offre('Site vitrine');
        $this->fabrique->flush();

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(404);
    }

    public function testContenusNonPubliesRenvoient404(): void
    {
        $secteur = $this->fabrique->secteur('Brouillon secteur', publie: false);
        $zone = $this->fabrique->zone('Brouillon zone', publie: false);
        $this->fabrique->offre('Brouillon offre', publie: false);
        $this->fabrique->realisation('Brouillon realisation', publie: false);
        $this->fabrique->flush();

        foreach ([
            '/offres/brouillon-offre',
            '/realisations/brouillon-realisation',
            '/site-internet-pour/brouillon-secteur',
            '/creation-site-internet/brouillon-zone',
            '/realisations?secteur=brouillon-secteur',
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(404, $url);
        }
    }

    public function testContenusNonPubliesAbsentsDesListes(): void
    {
        $this->fabrique->offre('Offre visible');
        $this->fabrique->offre('Offre cachée', publie: false);
        $this->fabrique->realisation('Réalisation visible', misEnAvant: true);
        $this->fabrique->realisation('Réalisation cachée', publie: false, misEnAvant: true);
        $this->fabrique->secteur('Secteur caché', publie: false);
        $this->fabrique->flush();

        foreach (['/', '/offres', '/realisations'] as $url) {
            $this->client->request('GET', $url);
            $html = $this->client->getResponse()->getContent();
            self::assertStringNotContainsString('cachée', $html, $url);
            self::assertStringNotContainsString('Secteur caché', $html, $url);
        }

        $this->client->request('GET', '/offres');
        self::assertSelectorTextContains('main', 'Offre visible');
    }

    public function testAccueilAfficheAuPlusTroisRealisationsMisesEnAvant(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->fabrique->realisation('Projet '.$i, misEnAvant: true);
        }
        $this->fabrique->realisation('Projet non mis en avant');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/');

        self::assertCount(3, $crawler->filter('.carte-realisation'));
        self::assertStringNotContainsString('Projet non mis en avant', $crawler->html());
    }

    public function testFiltreDesRealisationsParSecteur(): void
    {
        $artisans = $this->fabrique->secteur('Artisans');
        $communes = $this->fabrique->secteur('Communes');
        $this->fabrique->realisation('Menuiserie', $artisans);
        $this->fabrique->realisation('Mairie', $communes);
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/realisations?secteur=artisans');

        self::assertCount(1, $crawler->filter('.carte-realisation'));
        self::assertSelectorTextContains('.carte-realisation', 'Menuiserie');
        self::assertSelectorTextContains('.filtres [aria-current="page"]', 'Artisans');
        self::assertSelectorTextContains('h1', 'Artisans');
    }

    public function testTitreDuSecteurContientLeMotCle(): void
    {
        $this->fabrique->secteur('Gîtes', libelleCible: 'gîtes et chambres d\'hôtes');
        $this->fabrique->flush();

        $this->client->request('GET', '/site-internet-pour/gites');

        self::assertSelectorTextSame('h1', 'Site internet pour gîtes et chambres d\'hôtes');
    }

    public function testTitreDeLaZoneAvecLaBonnePreposition(): void
    {
        $this->fabrique->zone('Limoges', Zone::TYPE_VILLE);
        $this->fabrique->zone('Dordogne');
        $this->fabrique->zone('Indre')->setLieu('dans l\'Indre');
        $this->fabrique->flush();

        $this->client->request('GET', '/creation-site-internet/limoges');
        self::assertSelectorTextSame('h1', 'Création de site internet à Limoges');

        $this->client->request('GET', '/creation-site-internet/dordogne');
        self::assertSelectorTextSame('h1', 'Création de site internet en Dordogne');

        $this->client->request('GET', '/creation-site-internet/indre');
        self::assertSelectorTextSame('h1', 'Création de site internet dans l\'Indre');
    }

    public function testOffrePreselectionneeDansLeLienDeContact(): void
    {
        $this->fabrique->offre('Boutique en ligne');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/offres/boutique-en-ligne');

        self::assertGreaterThan(0, $crawler->filter('a[href="/contact?offre=boutique-en-ligne"]')->count());
    }

    public function testPrixSurDevisEtPrixMensuel(): void
    {
        $this->fabrique->offre('Sur mesure', prix: null);
        $this->fabrique->offre('Maintenance', prix: 39)->setPrixSuffixe('/mois');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/offres');

        $prix = $crawler->filter('.prix')->each(fn ($n) => preg_replace('/\s+/u', ' ', $n->text()));
        self::assertContains('Sur devis', $prix);
        self::assertContains('À partir de 39 € HT/mois', $prix);
    }

    public function testContenuHtmlAssainiEtSansSecondH1(): void
    {
        $this->fabrique->secteur('Artisans')
            ->setContenu('<h1>Titre saisi</h1><p onclick="alert(1)">Texte</p><script>alert(2)</script>');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/site-internet-pour/artisans');
        $prose = $crawler->filter('.prose')->html();

        self::assertCount(1, $crawler->filter('h1'));
        self::assertStringContainsString('<h2>Titre saisi</h2>', $prose);
        self::assertStringNotContainsString('<script', $prose);
        self::assertStringNotContainsString('onclick', $prose);
    }

    public function testLiensVersSecteurEtZoneNonPubliesMasques(): void
    {
        $secteur = $this->fabrique->secteur('Secteur brouillon', publie: false);
        $zone = $this->fabrique->zone('Zone publiée');
        $this->fabrique->realisation('Projet', $secteur, $zone);
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/realisations/projet');

        self::assertCount(0, $crawler->filter('a[href="/site-internet-pour/secteur-brouillon"]'));
        self::assertCount(1, $crawler->filter('.fiche a[href="/creation-site-internet/zone-publiee"]'));
    }

    public function testFilDArianeDesPagesDetail(): void
    {
        $zone = $this->fabrique->zone('Charente');
        $this->fabrique->offre('Site vitrine');
        $this->fabrique->flush();

        $crawler = $this->client->request('GET', '/offres/site-vitrine');
        self::assertSame(['Accueil', 'Offres', 'Site vitrine'], $crawler->filter('.fil-ariane li')->each(fn ($n) => trim($n->text())));

        $crawler = $this->client->request('GET', '/creation-site-internet/charente');
        self::assertSame(['Accueil', 'Zones d\'intervention', 'Charente'], $crawler->filter('.fil-ariane li')->each(fn ($n) => trim($n->text())));
    }
}
