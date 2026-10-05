<?php

namespace App\Tests\Entity;

use App\Entity\Offre;
use App\Entity\Secteur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Génération, normalisation et unicité des slugs (SluggableTrait + SlugUniqueListener).
 */
class SlugTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSlugGenereDepuisLeNom(): void
    {
        $offre = $this->creerOffre('Site vitrine « Été » & co');
        $this->em->flush();

        self::assertSame('site-vitrine-ete-co', $offre->getSlug());
    }

    public function testSlugSaisiALaMainEstNormalise(): void
    {
        $offre = $this->creerOffre('Boutique')->setSlug('Ma Boutique Éphémère');
        $this->em->flush();

        self::assertSame('ma-boutique-ephemere', $offre->getSlug());
    }

    public function testSlugsEnDoublonDansLeMemeFlush(): void
    {
        $a = $this->creerOffre('Site vitrine');
        $b = $this->creerOffre('Site vitrine');
        $c = $this->creerOffre('Site vitrine');
        $this->em->flush();

        self::assertSame(['site-vitrine', 'site-vitrine-2', 'site-vitrine-3'], [$a->getSlug(), $b->getSlug(), $c->getSlug()]);
    }

    public function testSlugEnDoublonAvecUneEntiteDejaEnBase(): void
    {
        $this->creerOffre('Maintenance');
        $this->em->flush();

        $doublon = $this->creerOffre('Maintenance');
        $this->em->flush();

        self::assertSame('maintenance-2', $doublon->getSlug());
    }

    public function testMemeSlugAutoriseEntreDeuxTypesDeContenu(): void
    {
        $offre = $this->creerOffre('Artisans');
        $secteur = (new Secteur())->setNom('Artisans');
        $this->em->persist($secteur);
        $this->em->flush();

        self::assertSame('artisans', $offre->getSlug());
        self::assertSame('artisans', $secteur->getSlug());
    }

    public function testSlugVideEnModificationEstRegenere(): void
    {
        $offre = $this->creerOffre('Sur mesure');
        $this->em->flush();

        $offre->setNom('Projet sur mesure')->setSlug(null);
        $this->em->flush();
        $this->em->clear();

        $recharge = $this->em->getRepository(Offre::class)->find($offre->getId());
        self::assertSame('projet-sur-mesure', $recharge->getSlug());
        self::assertNotNull($recharge->getUpdatedAt());
    }

    public function testModificationSansChangerLeSlugNeLeSuffixePas(): void
    {
        $offre = $this->creerOffre('Hébergement');
        $this->em->flush();

        $offre->setAccroche('Nouvelle accroche');
        $this->em->flush();

        self::assertSame('hebergement', $offre->getSlug());
    }

    private function creerOffre(string $nom): Offre
    {
        $offre = (new Offre())
            ->setNom($nom)
            ->setAccroche('Accroche')
            ->setDescription('Description');
        $this->em->persist($offre);

        return $offre;
    }
}
