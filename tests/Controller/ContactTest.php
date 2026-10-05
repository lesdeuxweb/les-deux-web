<?php

namespace App\Tests\Controller;

use App\Entity\MessageContact;
use App\Repository\MessageContactRepository;
use App\Tests\Fabrique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

/**
 * Formulaire de contact : succès, validation, anti-spam (honeypot, limite d'envois).
 */
class ContactTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // La limite d'envois est stockée dans le cache : on repart de zéro à chaque test.
        static::getContainer()->get('limiter.contact')->create('127.0.0.1')->reset();
    }

    public function testFormulaireAffiche(): void
    {
        $crawler = $this->client->request('GET', '/contact');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form.formulaire');
        foreach (['nom', 'email', 'telephone', 'entreprise', 'offre', 'message', 'consentement'] as $champ) {
            $id = 'contact_'.$champ;
            self::assertCount(1, $crawler->filter(sprintf('label[for="%s"]', $id)), sprintf('Label manquant pour %s.', $champ));
        }
        self::assertSelectorTextContains('main', 'rayon d\'environ 200 km');
    }

    public function testOffrePreselectionneeParParametre(): void
    {
        $fabrique = $this->fabrique();
        $fabrique->offre('Site vitrine');
        $fabrique->offre('Boutique en ligne');
        $fabrique->flush();

        $crawler = $this->client->request('GET', '/contact?offre=boutique-en-ligne');

        self::assertSame('boutique-en-ligne', $crawler->filter('#contact_offre option[selected]')->attr('value'));
    }

    public function testEnvoiValide(): void
    {
        $fabrique = $this->fabrique();
        $fabrique->offre('Site vitrine');
        $fabrique->flush();

        $this->soumettre(['contact[offre]' => 'site-vitrine']);

        self::assertResponseStatusCodeSame(303);
        self::assertResponseRedirects('/contact');

        // Enregistré en base, avec l'offre choisie
        $messages = $this->repository()->findAll();
        self::assertCount(1, $messages);
        self::assertSame('Marie Dupont', $messages[0]->getNom());
        self::assertSame('site-vitrine', $messages[0]->getOffre()?->getSlug());
        self::assertFalse($messages[0]->isTraite());

        // Notification à l'équipe + accusé de réception au prospect
        self::assertEmailCount(2);
        /** @var Email $notification */
        $notification = self::getMailerMessage(0);
        self::assertSame('contact@lesdeuxweb.test', $notification->getTo()[0]->getAddress());
        self::assertSame('marie@example.com', $notification->getReplyTo()[0]->getAddress());
        self::assertEmailHtmlBodyContains($notification, 'Site vitrine');
        self::assertEmailTextBodyContains($notification, 'un site pour mon gîte');

        /** @var Email $accuse */
        $accuse = self::getMailerMessage(1);
        self::assertSame('marie@example.com', $accuse->getTo()[0]->getAddress());
        self::assertEmailTextBodyNotContains($accuse, 'un site pour mon gîte');

        // Post/Redirect/Get : message de succès après redirection
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alerte--succes', 'votre message a bien été envoyé');
    }

    public function testErreursDeValidationSousLesChamps(): void
    {
        $crawler = $this->soumettre([
            'contact[nom]' => '',
            'contact[email]' => 'pas-un-email',
            'contact[telephone]' => 'abc',
            'contact[message]' => 'Court',
            'contact[consentement]' => false,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->repository()->count([]));
        self::assertEmailCount(0);

        $erreurs = [
            'nom' => 'Merci d\'indiquer votre nom.',
            'email' => 'Cette adresse email n\'est pas valide.',
            'telephone' => 'Ce numéro de téléphone n\'est pas valide.',
            'message' => 'trop court',
            'consentement' => 'Merci de cocher cette case',
        ];
        foreach ($erreurs as $champ => $texte) {
            $erreur = $crawler->filter(sprintf('#contact_%s_erreur', $champ));
            self::assertCount(1, $erreur, sprintf('Erreur absente pour %s.', $champ));
            self::assertStringContainsString($texte, $erreur->text());

            // Le message d'erreur suit le champ et lui est relié pour les lecteurs d'écran
            $champHtml = $crawler->filter('#contact_'.$champ);
            self::assertSame('true', $champHtml->attr('aria-invalid'));
            self::assertStringContainsString('contact_'.$champ.'_erreur', $champHtml->attr('aria-describedby'));
        }

        // Récapitulatif en haut du formulaire, avec un lien par champ en erreur
        self::assertStringContainsString('5 champs sont à corriger', $crawler->filter('.alerte--erreur')->text());
        self::assertCount(5, $crawler->filter('.alerte--erreur a[href^="#contact_"]'));

        // Les valeurs saisies sont conservées
        self::assertSame('pas-un-email', $crawler->filter('#contact_email')->attr('value'));
    }

    public function testHoneypotRejeteSilencieusement(): void
    {
        $this->soumettre(['contact[site_web]' => 'https://spam.example']);

        // Le robot voit un succès...
        self::assertResponseRedirects('/contact');
        $this->client->followRedirect();
        self::assertSelectorExists('.alerte--succes');

        // ... mais rien n'est enregistré ni envoyé
        self::assertSame(0, $this->repository()->count([]));
        self::assertEmailCount(0);
    }

    public function testLimiteDeCinqEnvoisParHeure(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->soumettre();
            self::assertResponseRedirects('/contact', 303, sprintf('Envoi n°%d refusé.', $i));
        }

        $crawler = $this->soumettre();

        self::assertResponseStatusCodeSame(429);
        self::assertStringContainsString('réessayer dans une heure', $crawler->filter('.alerte--erreur')->text());
        self::assertSame(5, $this->repository()->count([]));
    }

    public function testErreursDeSaisieNeComptentPasDansLaLimite(): void
    {
        for ($i = 1; $i <= 6; ++$i) {
            $this->soumettre(['contact[email]' => 'invalide']);
            self::assertResponseStatusCodeSame(422);
        }

        $this->soumettre();

        self::assertResponseRedirects('/contact');
    }

    public function testJetonCsrfDUnAutreSiteRefuse(): void
    {
        // Requête forgée depuis un autre site : la protection CSRF « stateless » vérifie l'en-tête Origin
        parse_str(http_build_query($this->donneesValides() + ['contact[_token]' => 'csrf-token']), $donnees);
        $this->client->request('POST', '/contact', $donnees, server: [
            'HTTP_ORIGIN' => 'https://site-malveillant.example',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->repository()->count([]));
    }

    /** @param array<string, mixed> $valeurs */
    private function soumettre(array $valeurs = []): Crawler
    {
        $crawler = $this->client->request('GET', '/contact');
        $form = $crawler->selectButton('Envoyer ma demande')->form();

        foreach ($this->donneesValides() + $valeurs as $champ => $valeur) {
            if ('contact[consentement]' === $champ) {
                /** @var ChoiceFormField $case */
                $case = $form[$champ];
                ($valeurs[$champ] ?? true) ? $case->tick() : $case->untick();
                continue;
            }
            $form[$champ] = $valeurs[$champ] ?? $valeur;
        }

        return $this->client->submit($form);
    }

    /** @return array<string, string> */
    private function donneesValides(): array
    {
        return [
            'contact[nom]' => 'Marie Dupont',
            'contact[email]' => 'marie@example.com',
            'contact[telephone]' => '05 55 12 34 56',
            'contact[entreprise]' => 'Gîte des Tilleuls',
            'contact[message]' => 'Bonjour, je voudrais un site pour mon gîte de trois chambres.',
            'contact[consentement]' => '1',
        ];
    }

    private function fabrique(): Fabrique
    {
        return new Fabrique(static::getContainer()->get(EntityManagerInterface::class));
    }

    private function repository(): MessageContactRepository
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getRepository(MessageContact::class);
    }
}
