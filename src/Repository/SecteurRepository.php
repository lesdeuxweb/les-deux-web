<?php

namespace App\Repository;

use App\Entity\Secteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Secteur>
 */
class SecteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Secteur::class);
    }

    /** @return Secteur[] */
    public function findPublie(): array
    {
        return $this->findBy(['publie' => true], ['position' => 'ASC', 'nom' => 'ASC']);
    }

    /**
     * Secteurs publiés ayant au moins une réalisation publiée (filtre de la page Réalisations).
     *
     * @return Secteur[]
     */
    public function findPubliesAvecRealisations(): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.realisations', 'r', 'WITH', 'r.publie = true')
            ->andWhere('s.publie = true')
            ->groupBy('s.id')
            ->orderBy('s.position', 'ASC')
            ->addOrderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOnePublieBySlug(string $slug): ?Secteur
    {
        return $this->findOneBy(['slug' => $slug, 'publie' => true]);
    }
}
