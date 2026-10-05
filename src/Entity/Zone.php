<?php

namespace App\Entity;

use App\Entity\Trait\SeoTrait;
use App\Entity\Trait\SluggableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Zone d'intervention en présentiel (ville ou département, ~200 km autour de Limoges).
 */
#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('slug', message: 'Ce slug est déjà utilisé.')]
class Zone
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

    public const TYPE_VILLE = 'ville';
    public const TYPE_DEPARTEMENT = 'departement';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_VILLE, self::TYPE_DEPARTEMENT])]
    private string $type = self::TYPE_DEPARTEMENT;

    /** Numéro du département, ex. "87". */
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $codeDepartement = null;

    /**
     * Lieu précédé de sa préposition, pour les titres : « dans l'Indre », « aux Eyzies »...
     * Laisser vide pour la règle par défaut (« à » pour une ville, « en » pour un département).
     */
    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $lieu = null;

    /** Paragraphe d'introduction en haut de page. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $accroche = null;

    /** Texte principal de la page : à rédiger spécifiquement, jamais en copier-coller. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $publie = false;

    /** @var Collection<int, Realisation> */
    #[ORM\OneToMany(targetEntity: Realisation::class, mappedBy: 'zone')]
    private Collection $realisations;

    public function __construct()
    {
        $this->realisations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    public function getSlugSource(): string
    {
        return $this->nom ?? '';
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCodeDepartement(): ?string
    {
        return $this->codeDepartement;
    }

    public function setCodeDepartement(?string $codeDepartement): static
    {
        $this->codeDepartement = $codeDepartement;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /** Ex. « à Limoges », « en Dordogne », ou la valeur saisie dans $lieu. */
    public function getLieuAvecPreposition(): string
    {
        if (null !== $this->lieu && '' !== trim($this->lieu)) {
            return trim($this->lieu);
        }

        return (self::TYPE_VILLE === $this->type ? 'à ' : 'en ').$this->nom;
    }

    public function getAccroche(): ?string
    {
        return $this->accroche;
    }

    public function setAccroche(?string $accroche): static
    {
        $this->accroche = $accroche;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

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

    /** @return Collection<int, Realisation> */
    public function getRealisations(): Collection
    {
        return $this->realisations;
    }

    public function addRealisation(Realisation $realisation): static
    {
        if (!$this->realisations->contains($realisation)) {
            $this->realisations->add($realisation);
            $realisation->setZone($this);
        }

        return $this;
    }

    public function removeRealisation(Realisation $realisation): static
    {
        if ($this->realisations->removeElement($realisation) && $realisation->getZone() === $this) {
            $realisation->setZone(null);
        }

        return $this;
    }
}
