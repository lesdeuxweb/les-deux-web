<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Title et meta description personnalisables pour chaque page publique.
 */
trait SeoTrait
{
    #[ORM\Column(length: 70, nullable: true)]
    #[Assert\Length(max: 70, maxMessage: 'Le titre SEO ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $metaTitle = null;

    #[ORM\Column(length: 160, nullable: true)]
    #[Assert\Length(max: 160, maxMessage: 'La meta description ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $metaDescription = null;

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }
}
