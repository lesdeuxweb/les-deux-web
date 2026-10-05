<?php

namespace App\Controller\Admin;

use App\Entity\Offre;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
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
            ->setDefaultSort(['position' => 'ASC', 'nom' => 'ASC'])
            ->setSearchFields(['nom', 'accroche']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu', 'fa fa-pen');
        yield TextField::new('nom', 'Nom de l\'offre');
        yield TextField::new('accroche', 'Accroche')
            ->setHelp('Une phrase affichée sur la carte de l\'offre.')
            ->hideOnIndex();
        yield TextEditorField::new('description', 'Description')->hideOnIndex();
        yield IntegerField::new('prixAPartirDe', 'Prix « à partir de » (€ HT)')
            ->setHelp('Laisser vide pour afficher « Sur devis ».')
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
    }
}
