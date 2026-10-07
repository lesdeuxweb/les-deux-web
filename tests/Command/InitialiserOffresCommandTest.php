<?php

namespace App\Tests\Command;

use App\Entity\Offre;
use App\Entity\OptionTarifaire;
use App\Tests\Fabrique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InitialiserOffresCommandTest extends KernelTestCase
{
    private CommandTester $tester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->tester = new CommandTester((new Application(self::bootKernel()))->find('app:initialiser-offres'));
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRemplitUneBaseVide(): void
    {
        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertSame(5, $this->em->getRepository(Offre::class)->count(['categorie' => Offre::CATEGORIE_CREATION]));
        self::assertSame(3, $this->em->getRepository(Offre::class)->count(['categorie' => Offre::CATEGORIE_ABONNEMENT]));
        self::assertSame(6, $this->em->getRepository(OptionTarifaire::class)->count([]));
        self::assertSame(3, $this->em->getRepository(Offre::class)->count(['enCarte' => true]));
        self::assertSame(399, $this->em->getRepository(Offre::class)->findOneBy(['nom' => 'La Vitrine'])->getPrixAPartirDe());
    }

    public function testNeTouchePasAUneBaseDejaRemplie(): void
    {
        $fabrique = new Fabrique($this->em);
        $fabrique->offre('Offre modifiée dans l\'admin', prix: 450);
        $fabrique->flush();

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString('contient déjà des offres', $this->tester->getDisplay());
        self::assertSame(1, $this->em->getRepository(Offre::class)->count([]));
    }
}
