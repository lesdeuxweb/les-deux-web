<?php

namespace App\Repository;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    /** @return Zone[] */
    public function findPublie(): array
    {
        return $this->findBy(['publie' => true], ['position' => 'ASC', 'nom' => 'ASC']);
    }

    public function findOnePublieBySlug(string $slug): ?Zone
    {
        return $this->findOneBy(['slug' => $slug, 'publie' => true]);
    }
}
