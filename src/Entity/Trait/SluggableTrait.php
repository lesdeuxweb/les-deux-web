<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Génère automatiquement un slug à partir de getSlugSource()
 * si aucun slug n'a été saisi dans le back-office.
 * Un slug saisi à la main est normalisé (minuscules, sans accent, tirets).
 * L'unicité est garantie par App\EventListener\SlugUniqueListener.
 * L'entité qui utilise ce trait doit avoir #[ORM\HasLifecycleCallbacks].
 */
trait SluggableTrait
{
    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    abstract public function getSlugSource(): string;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = (null === $slug || '' === trim($slug)) ? null : self::slugifier($slug);

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        if (null === $this->slug || '' === $this->slug) {
            $this->slug = self::slugifier($this->getSlugSource());
        }
    }

    private static function slugifier(string $texte): string
    {
        return (new AsciiSlugger('fr'))->slug($texte)->lower()->toString();
    }
}
