<?php

namespace App\Tests\Admin;

use App\Entity\MessageContact;
use App\Entity\User;
use App\Tests\Fabrique;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BackOfficeTest extends WebTestCase
{
    private const EMAIL = 'admin@example.com';
    private const MOT_DE_PASSE = 'motdepasse-de-test';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Les tentatives de connexion sont limitées : on repart de zéro à chaque test.
        static::getContainer()->get('cache.rate_limiter')->clear();
    }

    /** @return iterable<string, array{string}> */
    public static function pagesAdmin(): iterable
    {
        yield 'tableau de bord' => ['/admin'];
        yield 'offres' => ['/admin/offre'];
        yield 'options' => ['/admin/option-tarifaire'];
        yield 'messages' => ['/admin/message-contact'];
    }

    #[DataProvider('pagesAdmin')]
    public function testAccesRefuseSansConnexion(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseRedirects('/admin/connexion');
    }

    public function testAccesRefuseSansRoleAdmin(): void
    {
        $this->client->loginUser($this->creerUtilisateur(['ROLE_USER']));
        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testPageDeConnexionSansCookieNiIndexation(): void
    {
        $this->client->request('GET', '/admin/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSelectorExists('label[for="email"]');
        self::assertSelectorExists('label[for="mot_de_passe"]');
    }

    public function testConnexionReussie(): void
    {
        $this->creerUtilisateur();

        $this->seConnecter(self::EMAIL, self::MOT_DE_PASSE);

        self::assertResponseRedirects('/admin');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Tableau de bord');
    }

    public function testConnexionRefuseeAvecMauvaisMotDePasse(): void
    {
        $this->creerUtilisateur();

        $this->seConnecter(self::EMAIL, 'mauvais-mot-de-passe');

        self::assertResponseRedirects('/admin/connexion');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alerte--erreur', 'Identifiants invalides');
    }

    public function testConnexionBloqueeApresCinqEchecs(): void
    {
        $this->creerUtilisateur();

        for ($i = 0; $i < 5; ++$i) {
            $this->seConnecter(self::EMAIL, 'mauvais-mot-de-passe');
        }
        // Même le bon mot de passe est refusé pendant le blocage
        $this->seConnecter(self::EMAIL, self::MOT_DE_PASSE);

        self::assertResponseRedirects('/admin/connexion');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alerte--erreur', 'Trop de tentatives');
    }

    public function testTableauDeBordAfficheLesMessagesNonTraites(): void
    {
        foreach ([false, false, true] as $i => $traite) {
            $this->em->persist((new MessageContact())
                ->setNom('Prospect '.$i)
                ->setEmail('p'.$i.'@example.com')
                ->setMessage('Bonjour, un message de test.')
                ->setTraite($traite));
        }
        $this->em->flush();

        $this->client->loginUser($this->creerUtilisateur());
        $crawler = $this->client->request('GET', '/admin');

        self::assertSame('2', trim($crawler->filter('.tdb-messages__nombre')->text()));
        self::assertSelectorTextContains('.tdb-messages', 'messages non traités');
        self::assertSelectorExists('.tdb-messages--alerte');
    }

    #[DataProvider('pagesCrud')]
    public function testPagesDuBackOffice(string $url): void
    {
        $fabrique = new Fabrique($this->em);
        $fabrique->offre('Site 5 pages');
        $fabrique->option('Page supplémentaire', '50 €');
        $fabrique->flush();

        $this->client->loginUser($this->creerUtilisateur());
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    /** @return iterable<string, array{string}> */
    public static function pagesCrud(): iterable
    {
        foreach (['offre', 'option-tarifaire'] as $crud) {
            yield $crud.' : liste' => ['/admin/'.$crud];
            yield $crud.' : création' => ['/admin/'.$crud.'/new'];
        }
        yield 'messages : liste' => ['/admin/message-contact'];
    }

    public function testCreationDeMessageImpossible(): void
    {
        $this->client->loginUser($this->creerUtilisateur());
        $this->client->request('GET', '/admin/message-contact/new');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCreationDUneOffreDepuisLeBackOffice(): void
    {
        $this->client->loginUser($this->creerUtilisateur());
        $crawler = $this->client->request('GET', '/admin/offre/new');

        $form = $crawler->filter('form[name="Offre"]')->form([
            'Offre[nom]' => 'Refonte de site',
            'Offre[accroche]' => 'Votre site a besoin d\'un coup de neuf.',
            'Offre[prixAPartirDe]' => '450',
            'Offre[publie]' => '1',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->request('GET', '/');
        self::assertSelectorTextContains('#offres', 'Refonte de site');
        self::assertSelectorTextContains('#offres', '450');
    }


    /** @param list<string> $roles */
    private function creerUtilisateur(array $roles = ['ROLE_ADMIN']): User
    {
        $user = (new User())->setEmail(self::EMAIL)->setRoles($roles);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::MOT_DE_PASSE));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function seConnecter(string $email, string $motDePasse): void
    {
        $crawler = $this->client->request('GET', '/admin/connexion');
        $this->client->submit($crawler->selectButton('Se connecter')->form([
            'email' => $email,
            'mot_de_passe' => $motDePasse,
        ]));
    }
}
