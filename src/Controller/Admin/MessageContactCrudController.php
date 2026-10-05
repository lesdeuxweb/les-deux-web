<?php

namespace App\Controller\Admin;

use App\Entity\MessageContact;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Demandes reçues par le formulaire de contact : lecture seule, sauf la case « traité ».
 * Pas de création depuis l'admin.
 */
class MessageContactCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MessageContact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message')
            ->setEntityLabelInPlural('Messages de contact')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['nom', 'email', 'entreprise', 'message'])
            ->setPageTitle(Crud::PAGE_DETAIL, fn (MessageContact $m) => sprintf('Message de %s', $m->getNom()))
            ->setPageTitle(Crud::PAGE_EDIT, fn (MessageContact $m) => sprintf('Message de %s', $m->getNom()))
            ->setHelp(Crud::PAGE_INDEX, 'Cochez « Traité » une fois le prospect recontacté. Les messages sont à supprimer au bout de 3 ans (politique de confidentialité).');
    }

    public function configureActions(Actions $actions): Actions
    {
        $repondre = Action::new('repondre', 'Répondre par email', 'fa fa-reply')
            ->linkToUrl(fn (MessageContact $m) => 'mailto:'.$m->getEmail().'?subject='.rawurlencode('Votre demande de site internet'));

        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $repondre)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $a) => $a->setLabel('Marquer comme traité'))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn (Action $a) => $a->setLabel('Marquer comme traité'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('traite')->add('offre')->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        // Formulaire de modification : seule la case « traité » est modifiable.
        if (Crud::PAGE_EDIT === $pageName) {
            yield BooleanField::new('traite', 'Traité (prospect recontacté)');

            return;
        }

        yield DateTimeField::new('createdAt', 'Reçu le')->setFormat('dd/MM/yyyy HH:mm');
        yield TextField::new('nom', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TelephoneField::new('telephone', 'Téléphone');
        yield TextField::new('entreprise', 'Entreprise')->hideOnIndex();
        yield AssociationField::new('offre', 'Offre');
        yield TextareaField::new('message', 'Message')
            ->onlyOnDetail()
            ->renderAsHtml(false);
        yield TextField::new('message', 'Extrait')
            ->onlyOnIndex()
            ->setMaxLength(60);
        yield BooleanField::new('traite', 'Traité');
    }
}
