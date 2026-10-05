<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Champs communs aux contenus publics (Offre, Réalisation, Secteur, Zone) :
 * onglets « SEO » et « Publication ».
 */
trait ChampsContenuTrait
{
    /** @return iterable<\EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface> */
    private function ongletSeo(string $champSource): iterable
    {
        yield FormField::addTab('SEO', 'fa fa-magnifying-glass');
        yield SlugField::new('slug', 'Adresse de la page (slug)')
            ->setTargetFieldName($champSource)
            ->setRequired(false)
            ->setHelp('Partie de l\'adresse après le dernier « / ». Générée automatiquement si vide. Attention : la modifier change l\'adresse de la page.')
            ->hideOnIndex();
        yield TextField::new('metaTitle', 'Titre pour Google')
            ->setHelp('70 caractères maximum. Si vide, le titre est généré automatiquement.')
            ->setFormTypeOption('attr', ['maxlength' => 70])
            ->hideOnIndex();
        yield TextareaField::new('metaDescription', 'Description pour Google')
            ->setHelp('160 caractères maximum : le texte affiché sous le titre dans les résultats de recherche.')
            ->setFormTypeOption('attr', ['maxlength' => 160, 'rows' => 3])
            ->hideOnIndex();
    }

    /** @return iterable<\EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface> */
    private function ongletPublication(bool $avecPosition = true): iterable
    {
        yield FormField::addTab('Publication', 'fa fa-eye');
        yield BooleanField::new('publie', 'Publié')
            ->setHelp('Décoché : la page n\'est pas visible sur le site (brouillon).');
        if ($avecPosition) {
            yield IntegerField::new('position', 'Ordre d\'affichage')
                ->setHelp('Les plus petits nombres s\'affichent en premier.');
        }
        yield DateTimeField::new('updatedAt', 'Dernière modification')->onlyOnIndex();
    }
}
