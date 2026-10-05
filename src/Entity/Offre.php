<?php

namespace App\Entity;

use App\Entity\Trait\SeoTrait;
use App\Entity\Trait\SluggableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Offre commerciale : site vitrine, boutique en ligne, sur mesure, maintenance...
 */
#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('slug', message: 'Ce slug est déjà utilisé.')]
class Offre
{
    use SluggableTrait;
    use SeoTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $accroche = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    /** Prix "à partir de", en euros HT. Laisser vide pour "sur devis". */
    #[ORM\Column(name: 'prix_a_partir_de', nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $prixAPartirDe = null;

    /** Ex. "/mois" pour la maintenance, vide pour un prix unique. */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $prixSuffixe = null;

    /** Points forts, un par ligne dans le back-office. */
    #[ORM\Column(type: Types::JSON)]
    private array $pointsForts = [];

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $publie = false;

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    public function getSlugSource(): string
    {
        return $this->nom ?? '';
    }

    public function getPrixAffiche(): string
    {
        if (null === $this->prixAPartirDe) {
            return 'Sur devis';
        }

        return sprintf(
            'À partir de %s € HT%s',
            number_format($this->prixAPartirDe, 0, ',', ' '),
            $this->prixSuffixe ?? ''
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAccroche(): ?string
    {
        return $this->accroche;
    }

    public function setAccroche(string $accroche): static
    {
        $this->accroche = $accroche;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrixAPartirDe(): ?int
    {
        return $this->prixAPartirDe;
    }

    public function setPrixAPartirDe(?int $prixAPartirDe): static
    {
        $this->prixAPartirDe = $prixAPartirDe;

        return $this;
    }

    public function getPrixSuffixe(): ?string
    {
        return $this->prixSuffixe;
    }

    public function setPrixSuffixe(?string $prixSuffixe): static
    {
        $this->prixSuffixe = $prixSuffixe;

        return $this;
    }

    /** @return string[] */
    public function getPointsForts(): array
    {
        return $this->pointsForts;
    }

    /** @param string[] $pointsForts */
    public function setPointsForts(array $pointsForts): static
    {
        $this->pointsForts = array_values(array_filter(array_map('trim', $pointsForts)));

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isPublie(): bool
    {
        return $this->publie;
    }

    public function setPublie(bool $publie): static
    {
        $this->publie = $publie;

        return $this;
    }
}
