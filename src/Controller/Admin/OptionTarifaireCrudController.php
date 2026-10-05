<?php

namespace App\Controller\Admin;

use App\Entity\OptionTarifaire;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OptionTarifaireCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OptionTarifaire::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Option')
            ->setEntityLabelInPlural('Options en supplément')
            ->setDefaultSort(['position' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Tableau « En supplément » de la page Offres.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Option');
        yield TextField::new('prix', 'Prix')->setHelp('Texte libre : « 50 € », « 100 à 200 € », « Sur devis ».');
        yield IntegerField::new('position', 'Ordre d\'affichage');
        yield BooleanField::new('publie', 'Publiée');
    }
}
