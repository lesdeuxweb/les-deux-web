<?php

namespace App\Tests;

use App\Entity\Offre;
use App\Entity\OptionTarifaire;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée des contenus minimaux pour les tests (persistés, à flusher par l'appelant).
 * La base de test est remise à zéro après chaque test (DAMA DoctrineTestBundle).
 */
final class Fabrique
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function offre(string $nom, bool $publie = true, ?int $prix = 399, string $categorie = Offre::CATEGORIE_CREATION): Offre
    {
        return $this->persister((new Offre())
            ->setCategorie($categorie)
            ->setNom($nom)
            ->setAccroche('Accroche de '.$nom)
            ->setPrixAPartirDe($prix)
            ->setPointsForts(['Premier point fort', 'Deuxième point fort'])
            ->setPublie($publie));
    }

    public function option(string $nom, string $prix, bool $publie = true): OptionTarifaire
    {
        return $this->persister((new OptionTarifaire())->setNom($nom)->setPrix($prix)->setPublie($publie));
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    /**
     * @template T of object
     *
     * @param T $entite
     *
     * @return T
     */
    private function persister(object $entite): object
    {
        $this->em->persist($entite);

        return $entite;
    }
}
