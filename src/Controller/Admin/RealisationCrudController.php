<?php

namespace App\Controller\Admin;

use App\Entity\Realisation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class RealisationCrudController extends AbstractCrudController
{
    use ChampsContenuTrait;

    public static function getEntityFqcn(): string
    {
        return Realisation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réalisation')
            ->setEntityLabelInPlural('Réalisations')
            ->setDefaultSort(['dateRealisation' => 'DESC'])
            ->setSearchFields(['titre', 'client', 'ville']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('secteur')->add('zone')->add('publie')->add('misEnAvant');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu', 'fa fa-pen');
        yield ImageField::new('imageName', 'Image')
            ->setBasePath('/uploads/realisations')
            ->onlyOnIndex();
        yield TextField::new('titre', 'Titre');
        yield TextareaField::new('resume', 'Résumé')
            ->setHelp('Une ou deux phrases affichées sur la carte de la réalisation (255 caractères maximum).')
            ->setFormTypeOption('attr', ['maxlength' => 255, 'rows' => 2])
            ->hideOnIndex();
        yield TextEditorField::new('description', 'Description du projet')->hideOnIndex();
        yield TextField::new('client', 'Client')->hideOnIndex();
        yield TextField::new('ville', 'Ville');
        yield UrlField::new('url', 'Adresse du site')
            ->setHelp('Ex. https://www.exemple.fr. Affiche un bouton « Voir le site ».')
            ->hideOnIndex();
        yield DateField::new('dateRealisation', 'Date de mise en ligne')->hideOnIndex();
        yield AssociationField::new('secteur', 'Secteur');
        yield AssociationField::new('zone', 'Zone')->hideOnIndex();

        yield FormField::addTab('Image', 'fa fa-image');
        yield TextField::new('imageFile', 'Image')
            ->setFormType(VichImageType::class)
            ->setFormTypeOptions([
                'allow_delete' => true,
                'delete_label' => 'Supprimer l\'image',
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
            ])
            ->setHelp('JPEG, PNG ou WebP, 4 Mo maximum. Convertie automatiquement en WebP et redimensionnée (1600 px de large maximum). Format paysage conseillé.')
            ->onlyOnForms();
        yield TextField::new('imageAlt', 'Texte alternatif')
            ->setHelp('Décrit l\'image pour les personnes malvoyantes et pour Google. Ex. « Page d\'accueil du site du gîte, avec photo de la piscine ».')
            ->hideOnIndex();

        yield from $this->ongletSeo('titre');
        yield from $this->ongletPublication(avecPosition: false);
        yield BooleanField::new('misEnAvant', 'Mise en avant sur l\'accueil')
            ->setHelp('Les trois réalisations mises en avant les plus récentes apparaissent sur la page d\'accueil.');
    }
}
