<?php

namespace App\Form;

use App\Entity\MessageContact;
use App\Entity\Offre;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de contact. Les libellés sont des clés de traduction (domaine messages).
 */
class ContactType extends AbstractType
{
    /** Champ piège : invisible pour un humain, rempli par la plupart des robots. */
    public const CHAMP_PIEGE = 'site_web';

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'contact.form.nom',
                'attr' => ['autocomplete' => 'name', 'maxlength' => 100, 'placeholder' => 'contact.form.nom_exemple'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'contact.form.email',
                'attr' => ['autocomplete' => 'email', 'maxlength' => 180, 'placeholder' => 'contact.form.email_exemple'],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'contact.form.telephone',
                'required' => false,
                'attr' => ['autocomplete' => 'tel', 'maxlength' => 20, 'placeholder' => 'contact.form.telephone_exemple'],
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'contact.form.entreprise',
                'required' => false,
                'attr' => ['autocomplete' => 'organization', 'maxlength' => 150, 'placeholder' => 'contact.form.entreprise_exemple'],
            ])
            ->add('offre', EntityType::class, [
                'label' => 'contact.form.offre',
                'class' => Offre::class,
                // « Pack Essentiel » plutôt que « Essentiel » (évite la confusion avec « L'Essentiel »)
                'choice_label' => fn (Offre $o) => $o->isAbonnement() ? 'Pack '.$o->getNom() : $o->getNom(),
                'choice_value' => 'slug',
                'group_by' => fn (Offre $o) => $o->isAbonnement() ? 'contact.form.groupe_abonnements' : 'contact.form.groupe_creations',
                'required' => false,
                'placeholder' => 'contact.form.offre_aucune',
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('o')
                    ->andWhere('o.publie = true')
                    ->orderBy('o.categorie', 'DESC')
                    ->addOrderBy('o.position', 'ASC'),
            ])
            ->add('message', TextareaType::class, [
                'label' => 'contact.form.message',
                'attr' => ['rows' => 6, 'maxlength' => 5000, 'placeholder' => 'contact.form.message_exemple'],
            ])
            ->add('consentement', CheckboxType::class, [
                'mapped' => false,
                'label' => 'contact.form.consentement',
                'label_translation_parameters' => ['%url%' => $this->urlGenerator->generate('app_privacy')],
                'label_html' => true,
                'constraints' => [new Assert\IsTrue(message: 'contact.form.consentement_requis')],
            ])
            ->add(self::CHAMP_PIEGE, TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'contact.form.piege',
                'attr' => ['tabindex' => '-1', 'autocomplete' => 'off'],
                'row_attr' => ['class' => 'champ-piege', 'aria-hidden' => 'true'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessageContact::class,
            'translation_domain' => 'messages',
        ]);
    }
}
