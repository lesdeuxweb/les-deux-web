<?php

namespace App\Data;

use App\Entity\Offre;
use App\Entity\OptionTarifaire;

/**
 * Grille tarifaire de lancement : offres de création, packs d'abonnement et options.
 *
 * Utilisée par les données de démo (AppFixtures) et par la commande app:initialiser-offres
 * pour remplir la base de production lors de la mise en ligne. Ensuite, les offres se
 * modifient dans l'admin : ce fichier ne sert plus que de valeur initiale.
 */
final class GrilleTarifaire
{
    /**
     * @return list<Offre|OptionTarifaire> Entités prêtes à être persistées
     */
    public static function creerEntites(): array
    {
        // Grille tarifaire de lancement (prix sans TVA : franchise en base, voir site.yaml)
        // [nom, sur-titre, description, prix, « à partir de », points forts, en carte, badge]
        $creations = [
            ['L’Essentiel', 'Pour lancer votre activité', 'Une page unique, claire et convaincante pour présenter l’essentiel et recevoir vos premiers contacts.', 199, false,
                ['1 page complète', 'Adapté mobile & tablette', 'Formulaire de contact', 'Mise en ligne incluse'], true, null],
            ['Site 3 pages', null, 'Site vitrine simple, design personnalisé', 299, false,
                ['3 pages', 'Design personnalisé'], false, null],
            ['La Vitrine', 'Pour présenter votre entreprise', 'Un site vitrine structuré pour détailler votre activité, rassurer vos clients et gagner en visibilité.', 399, false,
                ['Jusqu’à 5 pages', 'Design personnalisé', 'Responsive & formulaire', 'SEO de base'], true, 'Notre conseil'],
            ['Site 8 pages', null, 'Site plus complet avec une structure avancée', 549, false,
                ['8 pages', 'Structure avancée'], false, null],
            ['Site sur mesure', 'Pour vos besoins spécifiques', 'Une solution métier conçue autour de votre fonctionnement, de vos clients et de vos objectifs.', 900, true,
                ['Réservation en ligne', 'Espace client', 'Catalogue ou fonctionnalités métier'], true, null],
        ];

        // [nom, sur-titre, description, prix, points forts, badge] ; affichés sous la mention « Pack »
        $abonnements = [
            ['Essentiel', 'Pour être tranquille', 'Votre site hébergé, sécurisé et sauvegardé, avec une petite modification de contenu chaque mois.', 15,
                ['Hébergement du site', 'SSL / HTTPS', 'Sauvegardes', 'Surveillance technique', 'Mises à jour de sécurité', '1 petite modification de contenu / mois', 'Texte, image ou coordonnées'], null],
            ['Évolution', 'Pour rester serein', 'Nous veillons sur votre site et le faisons évoluer au rythme de votre activité.', 35,
                ['Hébergement, SSL / HTTPS et sauvegardes', 'Surveillance et mises à jour techniques', 'Modifications de textes et images', 'Horaires, téléphone et adresse', 'Modification de boutons et liens', 'Petites modifications du design', 'Jusqu’à 30 min d’intervention / mois'], 'Le bon équilibre'],
            ['Sérénité', 'Pour être accompagné', 'Un accompagnement complet : nous faisons évoluer votre site et vous conseillons, en priorité.', 59,
                ['Hébergement, SSL / HTTPS et sauvegardes', 'Surveillance et mises à jour techniques', 'Modifications de textes, images et design', 'Création de petites sections', 'Petites fonctionnalités / développement', 'Jusqu’à 1 h d’intervention / mois', 'Conseils et traitement prioritaire'], null],
        ];

        $entites = [];
        $position = 0;
        foreach ($creations as [$nom, $surTitre, $accroche, $prix, $aPartirDe, $points, $enCarte, $badge]) {
            $entites[] = (new Offre())
                ->setCategorie(Offre::CATEGORIE_CREATION)
                ->setNom($nom)
                ->setSurTitre($surTitre)
                ->setAccroche($accroche)
                ->setPrixAPartirDe($prix)
                ->setAPartirDe($aPartirDe)
                ->setPointsForts($points)
                ->setEnCarte($enCarte)
                ->setBadge($badge)
                ->setPosition($position++)
                ->setPublie(true);
        }

        foreach ($abonnements as [$nom, $surTitre, $accroche, $prix, $points, $badge]) {
            $entites[] = (new Offre())
                ->setCategorie(Offre::CATEGORIE_ABONNEMENT)
                ->setNom($nom)
                ->setSurTitre($surTitre)
                ->setAccroche($accroche)
                ->setPrixAPartirDe($prix)
                ->setPrixSuffixe('/ mois')
                ->setPointsForts($points)
                ->setBadge($badge)
                ->setPosition($position++)
                ->setPublie(true);
        }

        $options = [
            ['Page supplémentaire', '50 €'],
            ['Logo / identité visuelle simple', '100–200 €'],
            ['Rédaction d’une page', '50–100 €'],
            ['Réservation en ligne', '250–400 €'],
            ['Catalogue produits', '200–500 €'],
            ['Fonctionnalité spécifique', 'Sur devis'],
        ];
        foreach ($options as $i => [$nom, $prix]) {
            $entites[] = (new OptionTarifaire())->setNom($nom)->setPrix($prix)->setPosition($i);
        }

        return $entites;
    }
}
