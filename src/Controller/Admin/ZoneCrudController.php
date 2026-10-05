<?php

namespace App\Controller\Admin;

use App\Entity\Zone;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ZoneCrudController extends AbstractCrudController
{
    use ChampsContenuTrait;

    public static function getEntityFqcn(): string
    {
        return Zone::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Zone')
            ->setEntityLabelInPlural('Zones d\'intervention')
            ->setDefaultSort(['position' => 'ASC', 'nom' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Chaque zone a sa page « Création de site internet à / en… », porte d\'entrée locale depuis Google.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu', 'fa fa-pen');
        yield TextField::new('nom', 'Nom');
        yield ChoiceField::new('type', 'Type')->setChoices([
            'Ville' => Zone::TYPE_VILLE,
            'Département' => Zone::TYPE_DEPARTEMENT,
        ]);
        yield TextField::new('codeDepartement', 'N° de département')
            ->setHelp('Ex. 87, 24, 2A.')
            ->setFormTypeOption('attr', ['maxlength' => 3]);
        yield TextField::new('lieu', 'Lieu avec préposition (facultatif)')
            ->setHelp('Utilisé dans le titre « Création de site internet… ». Par défaut : « à » + nom pour une ville, « en » + nom pour un département. À remplir pour les exceptions : « dans l\'Indre », « aux Sables-d\'Olonne ».')
            ->hideOnIndex();
        yield TextareaField::new('accroche', 'Accroche')
            ->setHelp('Paragraphe d\'introduction en haut de la page (255 caractères maximum).')
            ->setFormTypeOption('attr', ['maxlength' => 255, 'rows' => 3])
            ->hideOnIndex();
        yield TextEditorField::new('contenu', 'Contenu de la page')
            ->setHelp('Texte rédigé spécifiquement pour cette zone : évitez de copier celui d\'une autre zone (Google pénalise le contenu dupliqué).')
            ->hideOnIndex();

        yield from $this->ongletSeo('nom');
        yield from $this->ongletPublication();
    }
}
