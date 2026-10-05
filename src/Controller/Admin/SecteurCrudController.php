<?php

namespace App\Controller\Admin;

use App\Entity\Secteur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SecteurCrudController extends AbstractCrudController
{
    use ChampsContenuTrait;

    public static function getEntityFqcn(): string
    {
        return Secteur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Secteur')
            ->setEntityLabelInPlural('Secteurs (métiers)')
            ->setDefaultSort(['position' => 'ASC', 'nom' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Chaque secteur a sa page « Site internet pour… », porte d\'entrée depuis Google pour un métier.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu', 'fa fa-pen');
        yield TextField::new('nom', 'Nom du secteur');
        yield TextField::new('libelleCible', 'Libellé dans le titre')
            ->setHelp('Complète « Site internet pour… », au pluriel. Ex. « gîtes et chambres d\'hôtes ». Si vide : le nom en minuscules.')
            ->hideOnIndex();
        yield TextareaField::new('accroche', 'Accroche')
            ->setHelp('Paragraphe d\'introduction en haut de la page (255 caractères maximum).')
            ->setFormTypeOption('attr', ['maxlength' => 255, 'rows' => 3])
            ->hideOnIndex();
        yield TextEditorField::new('contenu', 'Contenu de la page')
            ->setHelp('Texte rédigé spécifiquement pour ce métier : évitez de copier celui d\'un autre secteur (Google pénalise le contenu dupliqué).')
            ->hideOnIndex();

        yield from $this->ongletSeo('nom');
        yield from $this->ongletPublication();
    }
}
