<?php

namespace App\Controller\Admin;

use App\Entity\Offre;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Offres de la section « Nos offres » de l'accueil.
 */
class OffreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Offre::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Offre')
            ->setEntityLabelInPlural('Offres')
            ->setDefaultSort(['categorie' => 'ASC', 'position' => 'ASC'])
            ->setSearchFields(['nom', 'accroche'])
            ->setHelp(Crud::PAGE_INDEX, 'Section « Nos offres » de l\'accueil : créations de site puis abonnements, dans l\'ordre d\'affichage.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu', 'fa fa-pen');
        yield ChoiceField::new('categorie', 'Catégorie')->setChoices(Offre::CATEGORIES);
        yield TextField::new('nom', 'Nom de l\'offre');
        yield TextField::new('surTitre', 'Sur-titre')
            ->setHelp('Petit texte au-dessus du nom sur la carte, ex. « Pour bien démarrer ».')
            ->hideOnIndex();
        yield TextareaField::new('accroche', 'Description courte')
            ->setHelp('Une ou deux phrases : texte de la carte, ou « Contenu principal » dans la grille complète.')
            ->setFormTypeOption('attr', ['maxlength' => 255, 'rows' => 3])
            ->hideOnIndex();
        yield ArrayField::new('pointsForts', 'Ce qui est compris')
            ->setHelp('Un point par ligne, affichés en liste à coches sur la carte.')
            ->hideOnIndex();

        yield FormField::addTab('Prix', 'fa fa-euro-sign');
        yield IntegerField::new('prixAPartirDe', 'Prix (€)')
            ->setHelp('Montant entier, sans « € ». Laisser vide pour afficher « Sur devis ».')
            ->hideOnIndex();
        yield BooleanField::new('aPartirDe', 'Afficher « À partir de »')
            ->setHelp('À cocher si le prix est indicatif (ex. Site métier). Décoché : prix fixe.')
            ->hideOnIndex();
        yield TextField::new('prixSuffixe', 'Suffixe du prix')
            ->setHelp('Ex. « / mois » pour un abonnement. Vide pour un prix unique.')
            ->hideOnIndex();
        yield TextField::new('prixAffiche', 'Prix affiché')->onlyOnIndex();

        yield FormField::addTab('Publication', 'fa fa-eye');
        yield BooleanField::new('publie', 'Publiée')
            ->setHelp('Décochée : l\'offre n\'apparaît pas sur le site.');
        yield IntegerField::new('position', 'Ordre d\'affichage')
            ->setHelp('Les plus petits nombres s\'affichent en premier.');
        yield BooleanField::new('enCarte', 'Afficher en carte')
            ->setHelp('Coché : grande carte dans la section « Nos offres » (3 conseillées). Décoché : ligne de la grille complète (« Voir toutes les offres »).');
        yield TextField::new('badge', 'Badge')
            ->setHelp('Pastille qui met la carte en avant (fond sombre), ex. « Notre conseil ». Vide : carte normale.');
    }
}
