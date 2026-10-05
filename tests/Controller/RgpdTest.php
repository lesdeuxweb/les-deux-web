<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Pages légales, cookies et mesure d'audience.
 */
class RgpdTest extends WebTestCase
{
    protected function tearDown(): void
    {
        // Rétablit la configuration par défaut (Matomo désactivé) pour les tests suivants
        foreach (['MATOMO_URL', 'MATOMO_SITE_ID'] as $variable) {
            $_SERVER[$variable] = $_ENV[$variable] = '';
        }
        parent::tearDown();
    }

    public function testMentionsLegales(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/mentions-legales');

        self::assertResponseIsSuccessful();
        $texte = $crawler->filter('main')->text();
        foreach (['Éditeur du site', 'SIRET', 'Directeur de la publication', 'Hébergeur', 'OVH SAS', '2 rue Kellermann, 59100 Roubaix, France', 'Propriété intellectuelle'] as $attendu) {
            self::assertStringContainsString($attendu, $texte);
        }
        // Informations de l'entreprise encore à fournir : repérées visuellement
        self::assertGreaterThan(0, $crawler->filter('main .a-completer')->count());
        self::assertSelectorExists('main a[href="/confidentialite"]');
    }

    public function testPolitiqueDeConfidentialite(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/confidentialite');

        self::assertResponseIsSuccessful();
        $texte = $crawler->filter('main')->text();
        foreach (['Données collectées', 'consentement', '3 ans', 'droit d\'accès', 'droit à l\'effacement', 'CNIL', 'aucun cookie publicitaire', 'OVH SAS'] as $attendu) {
            self::assertStringContainsString($attendu, $texte);
        }
        self::assertStringNotContainsString('Matomo', $texte, 'Matomo n\'est mentionné que s\'il est activé.');
    }

    public function testLiensLegauxDansLePiedDePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertCount(1, $crawler->filter('footer a[href="/mentions-legales"]'));
        self::assertCount(1, $crawler->filter('footer a[href="/confidentialite"]'));
    }

    public function testCaseDeConsentementLieeALaPolitique(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertCount(1, $crawler->filter('label[for="contact_consentement"] a[href="/confidentialite"]'));
    }

    public function testCookieDeSessionUniquementApresEnvoiDuFormulaire(): void
    {
        $client = static::createClient();
        static::getContainer()->get('limiter.contact')->create('127.0.0.1')->reset();

        $crawler = $client->request('GET', '/');
        self::assertSame([], $client->getResponse()->headers->getCookies(), 'Afficher le formulaire ne dépose aucun cookie.');

        $client->submit($crawler->selectButton('Envoyer ma demande')->form([
            'contact[nom]' => 'Marie Dupont',
            'contact[email]' => 'marie@example.com',
            'contact[message]' => 'Bonjour, je voudrais un site pour mon gîte.',
            'contact[consentement]' => true,
        ]));

        // Un seul cookie : la session technique qui porte le message de confirmation
        $cookies = $client->getResponse()->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertTrue($cookies[0]->isHttpOnly());
        self::assertSame(0, $cookies[0]->getExpiresTime(), 'Cookie de session : supprimé à la fermeture du navigateur.');
    }

    public function testCookieDeSessionSurLAdminApresConnexionSeulement(): void
    {
        $client = static::createClient();
        static::getContainer()->get('cache.rate_limiter')->clear();

        $client->request('GET', '/admin/connexion');
        self::assertSame([], $client->getResponse()->headers->getCookies(), 'La page de connexion ne dépose aucun cookie.');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $admin = (new User())->setEmail('admin@example.com')->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'motdepasse-de-test'));
        $em->persist($admin);
        $em->flush();

        $client->submit($client->getCrawler()->selectButton('Se connecter')->form([
            'email' => 'admin@example.com',
            'mot_de_passe' => 'motdepasse-de-test',
        ]));

        self::assertResponseRedirects('/admin');
        self::assertCount(1, $client->getResponse()->headers->getCookies());
    }

    public function testMatomoAbsentSansConfiguration(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertStringNotContainsString('_paq', $client->getResponse()->getContent());
    }

    public function testMatomoChargeSansCookieQuandConfigure(): void
    {
        $_SERVER['MATOMO_URL'] = $_ENV['MATOMO_URL'] = 'https://stats.example.fr/';
        $_SERVER['MATOMO_SITE_ID'] = $_ENV['MATOMO_SITE_ID'] = '3';

        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $html = $client->getResponse()->getContent();

        self::assertStringContainsString("_paq.push(['disableCookies'])", $html);
        // URL échappée pour JavaScript (https\u003A\/\/...) : on vérifie le domaine
        self::assertStringContainsString('stats.example.fr/', $html);
        self::assertStringContainsString("_paq.push(['setSiteId', '3'])", $html);

        // La politique de confidentialité le mentionne alors
        $crawler = $client->request('GET', '/confidentialite');
        self::assertStringContainsString('Matomo, configuré sans cookie', $crawler->filter('main')->text());
    }
}
