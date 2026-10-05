<?php

namespace App\DataFixtures;

use App\Entity\Offre;
use App\Entity\OptionTarifaire;
use App\Entity\Realisation;
use App\Entity\Secteur;
use App\Entity\User;
use App\Entity\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de démonstration (développement uniquement).
 * Offres et prix : grille tarifaire de lancement. Les réalisations et leurs clients sont fictifs.
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
        $secteurs = $this->chargerSecteurs($manager);
        $zones = $this->chargerZones($manager);
        $this->chargerRealisations($manager, $secteurs, $zones);
        $this->chargerAdmin($manager);

        $manager->flush();
    }

    private function chargerOffres(ObjectManager $manager): void
    {
        // Grille tarifaire de lancement (prix sans TVA : franchise en base, voir site.yaml)
        $creations = [
            ['Landing page', 'Pour une activité ou un événement', 'Une seule page claire et efficace pour présenter votre activité et recevoir des demandes.', 199, false,
                ['1 page', 'Adaptée aux mobiles', 'Formulaire de contact'], false, null],
            ['Site 3 pages', 'Pour démarrer simplement', 'Un site vitrine simple, avec un design personnalisé à vos couleurs.', 299, false,
                ['3 pages', 'Design personnalisé', 'Adapté aux mobiles', 'Formulaire de contact'], false, null],
            ['Site 5 pages', 'Pour bien démarrer', 'Une présence professionnelle et rassurante pour présenter votre activité et être trouvé localement.', 399, false,
                ['5 pages', 'Design personnalisé et adapté aux mobiles', 'Formulaire de contact', 'Référencement local de base'], true, 'Notre conseil'],
            ['Site 8 pages', 'Pour présenter toute votre activité', 'Un site plus complet, avec une structure avancée pour détailler vos services, réalisations et informations pratiques.', 549, false,
                ['8 pages', 'Structure avancée', 'Design personnalisé et adapté aux mobiles', 'Référencement local de base'], false, null],
            ['Site métier', 'Pour aller plus loin', 'Un site pensé autour de votre métier : réservation en ligne, espace client, catalogue... On construit l\'outil dont vous avez besoin.', 900, true,
                ['Réservation en ligne', 'Espace client', 'Catalogue produits', 'Fonctionnalités sur mesure'], true, null],
        ];

        $abonnements = [
            ['Pack Essentiel', 'Pour être tranquille', 'Votre site hébergé, sécurisé et sauvegardé, avec une petite modification de contenu chaque mois.', 15,
                ['Hébergement du site, SSL / HTTPS', 'Sauvegardes et surveillance technique', 'Mises à jour de sécurité', '1 petite modification de contenu par mois (texte, image ou coordonnées)'], false],
            ['Pack Évolution', 'Pour rester serein', 'Nous veillons sur votre site et le faisons évoluer au rythme de votre activité.', 35,
                ['Hébergement, SSL / HTTPS et sauvegardes', 'Surveillance et mises à jour techniques', 'Modifications de textes et d\'images', 'Modification des horaires, téléphone, adresse', 'Modification de boutons et liens', 'Petites modifications du design', 'Jusqu\'à 30 min d\'intervention par mois'], true],
            ['Pack Sérénité', 'Pour être accompagné', 'Un accompagnement complet : nous faisons évoluer votre site et vous conseillons, en priorité.', 59,
                ['Hébergement, SSL / HTTPS et sauvegardes', 'Surveillance et mises à jour techniques', 'Modifications de textes, d\'images et du design', 'Création de petites sections', 'Petites fonctionnalités et développements', 'Jusqu\'à 1 h d\'intervention par mois', 'Conseils et accompagnement', 'Traitement prioritaire des demandes'], false],
        ];

        $position = 0;
        foreach ($creations as [$nom, $surTitre, $accroche, $prix, $aPartirDe, $points, $surAccueil, $badge]) {
            $manager->persist((new Offre())
                ->setCategorie(Offre::CATEGORIE_CREATION)
                ->setNom($nom)
                ->setSurTitre($surTitre)
                ->setAccroche($accroche)
                ->setDescription('<p>'.htmlspecialchars($accroche).'</p><h2>Ce qui est compris</h2><ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $points)).'</li></ul><p>Chaque projet commence par un échange pour comprendre votre activité. Des options peuvent compléter l\'offre (pages supplémentaires, rédaction, logo...).</p>')
                ->setPrixAPartirDe($prix)
                ->setAPartirDe($aPartirDe)
                ->setPointsForts($points)
                ->setSurAccueil($surAccueil)
                ->setBadge($badge)
                ->setPosition($position++)
                ->setPublie(true));
        }

        foreach ($abonnements as [$nom, $surTitre, $accroche, $prix, $points, $surAccueil]) {
            $manager->persist((new Offre())
                ->setCategorie(Offre::CATEGORIE_ABONNEMENT)
                ->setNom($nom)
                ->setSurTitre($surTitre)
                ->setAccroche($accroche)
                ->setDescription('<p>'.htmlspecialchars($accroche).'</p><h2>Ce qui est compris</h2><ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $points)).'</li></ul><p>Sans engagement de durée.</p>')
                ->setPrixAPartirDe($prix)
                ->setPrixSuffixe('/ mois')
                ->setPointsForts($points)
                ->setSurAccueil($surAccueil)
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

    /** @return array<string, Secteur> */
    private function chargerSecteurs(ObjectManager $manager): array
    {
        $secteurs = [
            'gites' => [
                'nom' => 'Gîtes et chambres d\'hôtes',
                'cible' => 'gîtes et chambres d\'hôtes',
                'accroche' => 'Remplissez votre calendrier avec des réservations en direct, sans laisser 15 à 20 % de commission aux plateformes.',
                'contenu' => '<p>Les plateformes de réservation vous apportent de la visibilité, mais elles prennent une commission sur chaque nuit. Un site à votre nom permet de fidéliser vos clients et de recevoir les réservations en direct.</p><h2>Ce que votre site peut contenir</h2><ul><li>Une présentation de chaque chambre ou gîte, avec de belles photos</li><li>Le calendrier des disponibilités et les tarifs selon la saison</li><li>Les activités et adresses à découvrir autour de chez vous</li><li>Une version en anglais pour la clientèle étrangère</li></ul>',
            ],
            'artisans' => [
                'nom' => 'Artisans',
                'cible' => 'artisans du bâtiment et de l\'artisanat',
                'accroche' => 'Montrez vos chantiers, rassurez vos futurs clients et recevez des demandes de devis qualifiées.',
                'contenu' => '<p>Avant d\'appeler un artisan, la plupart des gens regardent sur internet. Un site clair, avec vos réalisations, votre zone d\'intervention et vos qualifications, fait souvent la différence.</p><h2>Ce que votre site peut contenir</h2><ul><li>Vos prestations, expliquées simplement</li><li>Une galerie de chantiers avant / après</li><li>Vos labels et assurances (RGE, Qualibat...)</li><li>Un formulaire de demande de devis</li></ul>',
            ],
            'producteurs' => [
                'nom' => 'Producteurs',
                'cible' => 'producteurs en vente directe',
                'accroche' => 'Faites connaître votre ferme, vos produits et vos points de vente, et prenez des commandes en ligne.',
                'contenu' => '<p>Vente à la ferme, marchés, paniers, AMAP : vos clients ont besoin de savoir où et quand vous trouver. Votre site rassemble ces informations et peut accepter les commandes à retirer sur place.</p><h2>Ce que votre site peut contenir</h2><ul><li>La présentation de votre exploitation et de vos méthodes</li><li>Vos produits et leur saisonnalité</li><li>Vos jours de marché et points de vente</li><li>La commande en ligne avec retrait à la ferme</li></ul>',
            ],
            'communes' => [
                'nom' => 'Communes',
                'cible' => 'communes et mairies',
                'accroche' => 'Un site communal clair, accessible et facile à tenir à jour par le secrétariat de mairie.',
                'contenu' => '<p>Horaires de la mairie, comptes rendus du conseil municipal, salle des fêtes, associations, démarches : les habitants cherchent ces informations en ligne. Votre site doit être simple à mettre à jour, même sans compétence technique.</p><h2>Ce que votre site peut contenir</h2><ul><li>Les actualités et l\'agenda de la commune</li><li>Les comptes rendus et arrêtés municipaux</li><li>L\'annuaire des associations et des commerces</li><li>Les démarches et liens vers service-public.fr</li></ul><p>Le site respecte les bases de l\'accessibilité (RGAA) et est hébergé en France.</p>',
            ],
        ];

        $entites = [];
        $position = 0;
        foreach ($secteurs as $cle => $donnees) {
            $manager->persist($entites[$cle] = (new Secteur())
                ->setNom($donnees['nom'])
                ->setLibelleCible($donnees['cible'])
                ->setAccroche($donnees['accroche'])
                ->setContenu($donnees['contenu'])
                ->setPosition($position++)
                ->setPublie(true));
        }

        return $entites;
    }

    /** @return array<string, Zone> */
    private function chargerZones(ObjectManager $manager): array
    {
        $zones = [
            'limoges' => ['Limoges', Zone::TYPE_VILLE, '87', 'Nos bureaux sont à Limoges : on peut se rencontrer chez vous, dans votre boutique ou autour d\'un café.', '<p>Commerçants du centre-ville, artisans de l\'agglomération, professions libérales : on accompagne les entreprises de Limoges et de Limoges Métropole, en face à face.</p><p>Être sur place, c\'est pouvoir passer prendre des photos de votre boutique, vous montrer le site sur votre ordinateur et vous former à sa mise à jour.</p>'],
            'haute-vienne' => ['Haute-Vienne', Zone::TYPE_DEPARTEMENT, '87', 'De Saint-Junien à Saint-Yrieix, de Bellac à Eymoutiers : on se déplace dans toute la Haute-Vienne.', '<p>La Haute-Vienne, c\'est notre département. Petites communes, gîtes des monts d\'Ambazac, artisans des bourgs ruraux : on connaît le territoire et ses besoins.</p>'],
            'charente' => ['Charente', Zone::TYPE_DEPARTEMENT, '16', 'Angoulême, Cognac, Confolens, la Charente limousine : on intervient dans toute la Charente.', '<p>De la Charente limousine aux vignobles du Cognaçais, on accompagne commerces, producteurs et hébergements. Une partie de la clientèle étant anglophone, on peut prévoir votre site en deux langues.</p>'],
            'dordogne' => ['Dordogne', Zone::TYPE_DEPARTEMENT, '24', 'Périgord vert, blanc, pourpre ou noir : on crée des sites pour les entreprises et hébergements de Dordogne.', '<p>En Dordogne, le tourisme fait vivre de nombreux gîtes, chambres d\'hôtes et producteurs. Un site bilingue français-anglais permet de toucher la clientèle étrangère, nombreuse dans le département.</p>'],
        ];

        $entites = [];
        $position = 0;
        foreach ($zones as $cle => [$nom, $type, $code, $accroche, $contenu]) {
            $manager->persist($entites[$cle] = (new Zone())
                ->setNom($nom)
                ->setType($type)
                ->setCodeDepartement($code)
                ->setAccroche($accroche)
                ->setContenu($contenu)
                ->setPosition($position++)
                ->setPublie(true));
        }

        return $entites;
    }

    /**
     * @param array<string, Secteur> $secteurs
     * @param array<string, Zone>    $zones
     */
    private function chargerRealisations(ObjectManager $manager, array $secteurs, array $zones): void
    {
        $realisations = [
            ['Gîte Les Hauts de la Dronne', 'Gîte Les Hauts de la Dronne (exemple fictif)', 'Brantôme', 'Un site bilingue pour un gîte de quatre chambres, avec calendrier des disponibilités.', 'gites', 'dordogne', '2026-04-01', '<p>Les propriétaires recevaient la plupart de leurs réservations via des plateformes. Ils souhaitaient un site à leur nom, en français et en anglais, pour accueillir leurs clients fidèles en direct.</p><h2>Ce que nous avons fait</h2><ul><li>Une page par chambre avec photos et équipements</li><li>Un calendrier des disponibilités mis à jour par les propriétaires</li><li>Une page « Autour du gîte » avec leurs bonnes adresses</li></ul>'],
            ['Menuiserie Vergnaud', 'Menuiserie Vergnaud (exemple fictif)', 'Saint-Junien', 'Le site d\'un menuisier : réalisations en photos, zone d\'intervention et demandes de devis.', 'artisans', 'haute-vienne', '2026-02-15', '<p>Menuisier installé depuis vingt ans, le client n\'avait qu\'une page sur un annuaire. Il voulait montrer ses réalisations et recevoir des demandes plus précises.</p><h2>Ce que nous avons fait</h2><ul><li>Une galerie de chantiers classés par type (escaliers, cuisines, fenêtres)</li><li>Un formulaire de devis avec envoi de photos</li><li>Une formation d\'une heure pour ajouter ses chantiers lui-même</li></ul>'],
            ['Commune de Montrollet-sur-Vienne', 'Mairie de Montrollet-sur-Vienne (exemple fictif)', 'Montrollet-sur-Vienne', 'Un site communal simple, mis à jour chaque semaine par la secrétaire de mairie.', 'communes', 'charente', '2025-11-10', '<p>La commune de 600 habitants avait un ancien site devenu impossible à modifier. La secrétaire de mairie voulait pouvoir publier les actualités et les comptes rendus elle-même.</p><h2>Ce que nous avons fait</h2><ul><li>Un site respectant les bases de l\'accessibilité</li><li>Un agenda et un annuaire des associations</li><li>Une demi-journée de formation en mairie</li></ul>'],
        ];

        foreach ($realisations as [$titre, $client, $ville, $resume, $secteur, $zone, $date, $description]) {
            $manager->persist((new Realisation())
                ->setTitre($titre)
                ->setClient($client)
                ->setVille($ville)
                ->setResume($resume)
                ->setDescription($description)
                ->setSecteur($secteurs[$secteur])
                ->setZone($zones[$zone])
                ->setDateRealisation(new \DateTimeImmutable($date))
                ->setPublie(true)
                ->setMisEnAvant(true));
        }
    }

    private function chargerAdmin(ObjectManager $manager): void
    {
        $admin = (new User())->setEmail(self::ADMIN_EMAIL)->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::ADMIN_MOT_DE_PASSE));
        $manager->persist($admin);
    }
}
