<?php

namespace App\Tests;

use App\Entity\Offre;
use App\Entity\Realisation;
use App\Entity\Secteur;
use App\Entity\Zone;
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

    public function offre(string $nom, bool $publie = true, ?int $prix = 900): Offre
    {
        return $this->persister((new Offre())
            ->setNom($nom)
            ->setAccroche('Accroche de '.$nom)
            ->setDescription('<p>Description de '.$nom.'</p>')
            ->setPrixAPartirDe($prix)
            ->setPointsForts(['Premier point fort', 'Deuxième point fort'])
            ->setPublie($publie));
    }

    public function secteur(string $nom, bool $publie = true, ?string $libelleCible = null): Secteur
    {
        return $this->persister((new Secteur())
            ->setNom($nom)
            ->setLibelleCible($libelleCible)
            ->setAccroche('Accroche du secteur '.$nom)
            ->setContenu('<p>Contenu du secteur '.$nom.'</p>')
            ->setPublie($publie));
    }

    public function zone(string $nom, string $type = Zone::TYPE_DEPARTEMENT, bool $publie = true): Zone
    {
        return $this->persister((new Zone())
            ->setNom($nom)
            ->setType($type)
            ->setAccroche('Accroche de la zone '.$nom)
            ->setContenu('<p>Contenu de la zone '.$nom.'</p>')
            ->setPublie($publie));
    }

    public function realisation(string $titre, ?Secteur $secteur = null, ?Zone $zone = null, bool $publie = true, bool $misEnAvant = false): Realisation
    {
        return $this->persister((new Realisation())
            ->setTitre($titre)
            ->setResume('Résumé de '.$titre)
            ->setDescription('<p>Description de '.$titre.'</p>')
            ->setVille('Limoges')
            ->setSecteur($secteur)
            ->setZone($zone)
            ->setPublie($publie)
            ->setMisEnAvant($misEnAvant));
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
