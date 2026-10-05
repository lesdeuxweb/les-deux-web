<?php

namespace App\Repository;

use App\Entity\Realisation;
use App\Entity\Secteur;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Realisation>
 */
class RealisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Realisation::class);
    }

    /**
     * Réalisations publiées, éventuellement filtrées par secteur et/ou zone.
     *
     * @return Realisation[]
     */
    public function findPubliees(?Secteur $secteur = null, ?Zone $zone = null, ?int $limit = null): array
    {
        $qb = $this->publieesQb();

        if (null !== $secteur) {
            $qb->andWhere('r.secteur = :secteur')->setParameter('secteur', $secteur);
        }

        if (null !== $zone) {
            $qb->andWhere('r.zone = :zone')->setParameter('zone', $zone);
        }

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }

    /** @return Realisation[] Pour la page d'accueil. */
    public function findMisesEnAvant(int $limit = 3): array
    {
        return $this->publieesQb()
            ->andWhere('r.misEnAvant = true')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOnePublieeBySlug(string $slug): ?Realisation
    {
        return $this->publieesQb()
            ->andWhere('r.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function publieesQb(): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.secteur', 's')->addSelect('s')
            ->leftJoin('r.zone', 'z')->addSelect('z')
            ->andWhere('r.publie = true')
            ->orderBy('r.dateRealisation', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC');
    }
}
