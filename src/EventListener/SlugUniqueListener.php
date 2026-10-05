<?php

namespace App\EventListener;

use App\Entity\Trait\SluggableTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Rend unique le slug des entités utilisant SluggableTrait en ajoutant
 * un suffixe (-2, -3...) si le slug est déjà pris par une autre entité
 * de la même classe.
 *
 * Doctrine exécute les callbacks de l'entité (generateSlug) avant ce listener :
 * le slug est donc déjà généré et normalisé quand on arrive ici.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class SlugUniqueListener
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->rendreUnique($args->getObject(), $args->getObjectManager());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // Doctrine recalcule le changeset après les listeners preUpdate :
        // la modification du slug est bien prise en compte.
        $this->rendreUnique($args->getObject(), $args->getObjectManager());
    }

    private function rendreUnique(object $entite, EntityManagerInterface $em): void
    {
        if (!\in_array(SluggableTrait::class, class_uses($entite), true)) {
            return;
        }

        $base = $entite->getSlug();
        if (null === $base || '' === $base) {
            return;
        }

        $candidat = $base;
        $suffixe = 2;
        while ($this->estPris($candidat, $entite, $em)) {
            $candidat = $base.'-'.$suffixe++;
        }

        $entite->setSlug($candidat);
    }

    private function estPris(string $slug, object $entite, EntityManagerInterface $em): bool
    {
        // Entités persistées dans le même flush, pas encore en base (ex. fixtures).
        foreach ($em->getUnitOfWork()->getScheduledEntityInsertions() as $autre) {
            if ($autre !== $entite && $autre::class === $entite::class && $autre->getSlug() === $slug) {
                return true;
            }
        }

        $existant = $em->getRepository($entite::class)->findOneBy(['slug' => $slug]);

        return null !== $existant && $existant !== $entite;
    }
}
