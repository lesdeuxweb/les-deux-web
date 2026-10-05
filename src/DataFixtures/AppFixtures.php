<?php

namespace App\DataFixtures;

use App\Entity\Offre;
use App\Entity\OptionTarifaire;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de démonstration (développement uniquement).
 * Offres et prix : grille tarifaire de lancement.
 *
 *   php bin/console doctrine:fixtures:load
 *
 * Admin de dev : admin@lesdeuxweb.test / admin-dev-lesdeuxweb
 */
class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'admin@lesdeuxweb.test';
    public const ADMIN_MOT_DE_PASSE = 'admin-dev-lesdeuxweb';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->chargerOffres($manager);
        $this->chargerAdmin($manager);

        $manager->flush();
    }

    private function chargerOffres(ObjectManager $manager): void
    {
        // Grille tarifaire de lancement (prix sans TVA : franchise en base, voir site.yaml)
        $creations = [
            ['Landing page', 'Pour une activité ou un événement', 'Une seule page claire et efficace pour présenter votre activité et recevoir des demandes.', 199, false,
                ['1 page', 'Adaptée aux mobiles', 'Formulaire de contact'], null],
            ['Site 3 pages', 'Pour démarrer simplement', 'Un site vitrine simple, avec un design personnalisé à vos couleurs.', 299, false,
                ['3 pages', 'Design personnalisé', 'Adapté aux mobiles', 'Formulaire de contact'], null],
            ['Site 5 pages', 'Pour bien démarrer', 'Une présence professionnelle et rassurante pour présenter votre activité et être trouvé localement.', 399, false,
                ['5 pages', 'Design personnalisé et adapté aux mobiles', 'Formulaire de contact', 'Référencement local de base'], 'Notre conseil'],
            ['Site 8 pages', 'Pour présenter toute votre activité', 'Un site plus complet, avec une structure avancée pour détailler vos services, réalisations et informations pratiques.', 549, false,
                ['8 pages', 'Structure avancée', 'Design personnalisé et adapté aux mobiles', 'Référencement local de base'], null],
            ['Site métier', 'Pour aller plus loin', 'Un site pensé autour de votre métier : réservation en ligne, espace client, catalogue... On construit l\'outil dont vous avez besoin.', 900, true,
                ['Réservation en ligne', 'Espace client', 'Catalogue produits', 'Fonctionnalités sur mesure'], null],
        ];

        $abonnements = [
            ['Pack Essentiel', 'Pour être tranquille', 'Votre site hébergé, sécurisé et sauvegardé, avec une petite modification de contenu chaque mois.', 15,
                ['Hébergement du site, SSL / HTTPS', 'Sauvegardes et surveillance technique', 'Mises à jour de sécurité', '1 petite modification de contenu par mois (texte, image ou coordonnées)']],
            ['Pack Évolution', 'Pour rester serein', 'Nous veillons sur votre site et le faisons évoluer au rythme de votre activité.', 35,
                ['Hébergement, SSL / HTTPS et sauvegardes', 'Surveillance et mises à jour techniques', 'Modifications de textes et d\'images', 'Modification des horaires, téléphone, adresse', 'Modification de boutons et liens', 'Petites modifications du design', 'Jusqu\'à 30 min d\'intervention par mois']],
            ['Pack Sérénité', 'Pour être accompagné', 'Un accompagnement complet : nous faisons évoluer votre site et vous conseillons, en priorité.', 59,
                ['Hébergement, SSL / HTTPS et sauvegardes', 'Surveillance et mises à jour techniques', 'Modifications de textes, d\'images et du design', 'Création de petites sections', 'Petites fonctionnalités et développements', 'Jusqu\'à 1 h d\'intervention par mois', 'Conseils et accompagnement', 'Traitement prioritaire des demandes']],
        ];

        $position = 0;
        foreach ($creations as [$nom, $surTitre, $accroche, $prix, $aPartirDe, $points, $badge]) {
            $manager->persist((new Offre())
                ->setCategorie(Offre::CATEGORIE_CREATION)
                ->setNom($nom)
                ->setSurTitre($surTitre)
                ->setAccroche($accroche)
                ->setPrixAPartirDe($prix)
                ->setAPartirDe($aPartirDe)
                ->setPointsForts($points)
                ->setBadge($badge)
                ->setPosition($position++)
                ->setPublie(true));
        }

        foreach ($abonnements as [$nom, $surTitre, $accroche, $prix, $points]) {
            $manager->persist((new Offre())
                ->setCategorie(Offre::CATEGORIE_ABONNEMENT)
                ->setNom($nom)
                ->setSurTitre($surTitre)
                ->setAccroche($accroche)
                ->setPrixAPartirDe($prix)
                ->setPrixSuffixe('/ mois')
                ->setPointsForts($points)
                ->setPosition($position++)
                ->setPublie(true));
        }

        $options = [
            ['Page supplémentaire', '50 €'],
            ['Logo / identité visuelle simple', '100 à 200 €'],
            ['Rédaction d\'une page', '50 à 100 €'],
            ['Réservation en ligne', '250 à 400 €'],
            ['Catalogue produits', '200 à 500 €'],
            ['Fonctionnalité spécifique', 'Sur devis'],
        ];
        foreach ($options as $i => [$nom, $prix]) {
            $manager->persist((new OptionTarifaire())->setNom($nom)->setPrix($prix)->setPosition($i));
        }
    }

    private function chargerAdmin(ObjectManager $manager): void
    {
        $admin = (new User())->setEmail(self::ADMIN_EMAIL)->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::ADMIN_MOT_DE_PASSE));
        $manager->persist($admin);
    }
}
