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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OffreCrudController extends AbstractCrudController
{
    use ChampsContenuTrait;

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
            ->setSearchFields(['nom', 'accroche']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu', 'fa fa-pen');
        yield ChoiceField::new('categorie', 'Catégorie')->setChoices(Offre::CATEGORIES);
        yield TextField::new('nom', 'Nom de l\'offre');
        yield TextField::new('surTitre', 'Sur-titre')
            ->setHelp('Petit texte au-dessus du nom sur la carte, ex. « Pour bien démarrer ».')
            ->hideOnIndex();
        yield TextField::new('accroche', 'Accroche')
            ->setHelp('Une phrase affichée sur la carte de l\'offre.')
            ->hideOnIndex();
        yield TextEditorField::new('description', 'Description')->hideOnIndex();
        yield IntegerField::new('prixAPartirDe', 'Prix (€)')
            ->setHelp('Montant entier, sans « € ». Laisser vide pour afficher « Sur devis ».')
            ->hideOnIndex();
        yield BooleanField::new('aPartirDe', 'Afficher « À partir de »')
            ->setHelp('À cocher si le prix est indicatif (ex. Site métier). Décoché : prix fixe.')
            ->hideOnIndex();
        yield TextField::new('prixSuffixe', 'Suffixe du prix')
            ->setHelp('Ex. « /mois » pour un abonnement. Vide pour un prix unique.')
            ->hideOnIndex();
        yield TextField::new('prixAffiche', 'Prix affiché')->onlyOnIndex();
        yield ArrayField::new('pointsForts', 'Points forts')
            ->setHelp('Un point par ligne. Les trois premiers apparaissent sur la carte de l\'offre.')
            ->hideOnIndex();

        yield from $this->ongletSeo('nom');
        yield from $this->ongletPublication();
        yield BooleanField::new('surAccueil', 'Afficher sur l\'accueil')
            ->setHelp('Sélection d\'offres de la page d\'accueil (3 conseillées).');
        yield TextField::new('badge', 'Badge')
            ->setHelp('Pastille sur la carte, ex. « Notre conseil ». Vide : aucune pastille.')
            ->hideOnIndex();
    }
}
