<?php

namespace App\DataFixtures;

use App\Entity\Offre;
use App\Entity\Realisation;
use App\Entity\Secteur;
use App\Entity\User;
use App\Entity\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de démonstration (développement uniquement).
 * Les prix sont provisoires ; les réalisations et leurs clients sont fictifs.
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
        $offres = [
            [
                'nom' => 'Site vitrine',
                'accroche' => 'Pour être trouvé sur internet et rassurer vos clients avant qu\'ils vous appellent.',
                'prix' => 900,
                'suffixe' => null,
                'points' => ['Jusqu\'à 6 pages, rédigées avec vous', 'Adapté aux téléphones et tablettes', 'Formulaire de contact et plan d\'accès', 'Formation pour modifier vos textes et photos'],
                'description' => '<p>Le site vitrine est l\'offre idéale pour présenter votre activité, vos services et vos coordonnées. On commence par un rendez-vous pour comprendre votre métier et vos clients, puis on construit ensemble l\'arborescence et les textes.</p><h2>Ce qui est compris</h2><ul><li>Un design à vos couleurs, sobre et lisible</li><li>Le référencement de base sur Google (titres, descriptions, fiche Google Business conseillée)</li><li>Un back-office simple pour faire vos mises à jour</li></ul><p>Comptez en général trois à cinq semaines entre le premier rendez-vous et la mise en ligne.</p>',
            ],
            [
                'nom' => 'Boutique en ligne',
                'accroche' => 'Vendez vos produits en ligne, avec paiement sécurisé et retrait sur place ou livraison.',
                'prix' => 2500,
                'suffixe' => null,
                'points' => ['Catalogue produits facile à gérer', 'Paiement en ligne sécurisé', 'Retrait sur place, livraison ou les deux', 'Suivi des commandes par email'],
                'description' => '<p>Pour les producteurs, artisans et commerçants qui veulent vendre en ligne sans dépendre d\'une place de marché. Vous gardez la relation avec vos clients et vos marges.</p><h2>Ce qui est compris</h2><ul><li>Mise en place du catalogue et des premiers produits</li><li>Paramétrage du paiement et des modes de livraison</li><li>Pages légales (CGV, mentions légales) à compléter avec vous</li></ul>',
            ],
            [
                'nom' => 'Sur mesure',
                'accroche' => 'Réservation en ligne, espace adhérents, démarches pour une mairie : on construit l\'outil dont vous avez besoin.',
                'prix' => null,
                'suffixe' => null,
                'points' => ['Étude de votre besoin sans engagement', 'Développement sur mesure (Symfony)', 'Hébergement en France'],
                'description' => '<p>Certains projets ne rentrent pas dans une case : un calendrier de réservation pour un gîte, un annuaire des associations pour une commune, un espace client... On étudie votre besoin et on vous propose un devis détaillé, poste par poste.</p>',
            ],
            [
                'nom' => 'Hébergement et maintenance',
                'accroche' => 'On s\'occupe de l\'hébergement, des mises à jour et des sauvegardes. Vous, vous gérez votre activité.',
                'prix' => 39,
                'suffixe' => '/mois',
                'points' => ['Hébergement chez OVH, en France', 'Mises à jour de sécurité et sauvegardes', 'Petites modifications incluses', 'Un interlocuteur joignable'],
                'description' => '<p>Un site internet a besoin d\'entretien : mises à jour de sécurité, sauvegardes, renouvellement du nom de domaine et du certificat. Avec cette formule, on s\'en occupe pour vous.</p><p>Sans engagement de durée : vous pouvez arrêter quand vous voulez, et on vous remet l\'ensemble de votre site.</p>',
            ],
        ];

        foreach ($offres as $position => $donnees) {
            $manager->persist((new Offre())
                ->setNom($donnees['nom'])
                ->setAccroche($donnees['accroche'])
                ->setDescription($donnees['description'])
                ->setPrixAPartirDe($donnees['prix'])
                ->setPrixSuffixe($donnees['suffixe'])
                ->setPointsForts($donnees['points'])
                ->setPosition($position)
                ->setPublie(true));
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
