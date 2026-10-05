<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    /** @return Offre[] */
    public function findPublie(): array
    {
        return $this->findBy(['publie' => true], ['position' => 'ASC', 'nom' => 'ASC']);
    }

    /** @return Offre[] */
    public function findPublieesParCategorie(string $categorie): array
    {
        return $this->findBy(['publie' => true, 'categorie' => $categorie], ['position' => 'ASC', 'nom' => 'ASC']);
    }

    /**
     * Offres publiées d'une catégorie, affichées en carte ou non.
     *
     * @return Offre[]
     */
    public function findPublieesParCategorieEtCarte(string $categorie, bool $enCarte): array
    {
        return $this->findBy(['publie' => true, 'categorie' => $categorie, 'enCarte' => $enCarte], ['position' => 'ASC', 'nom' => 'ASC']);
    }

    public function findOnePublieBySlug(string $slug): ?Offre
    {
        return $this->findOneBy(['slug' => $slug, 'publie' => true]);
    }
}
