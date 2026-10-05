<?php

namespace App\Repository;

use App\Entity\OptionTarifaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OptionTarifaire>
 */
class OptionTarifaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OptionTarifaire::class);
    }

    /** @return OptionTarifaire[] */
    public function findPublie(): array
    {
        return $this->findBy(['publie' => true], ['position' => 'ASC', 'nom' => 'ASC']);
    }
}
