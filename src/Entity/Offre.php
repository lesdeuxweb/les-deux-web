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
 * Offre commerciale : création de site (prix unique) ou abonnement (prix mensuel).
 */
#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('slug', message: 'Ce slug est déjà utilisé.')]
class Offre
{
    use SluggableTrait;
    use SeoTrait;
    use TimestampableTrait;

    public const CATEGORIE_CREATION = 'creation';
    public const CATEGORIE_ABONNEMENT = 'abonnement';
    public const CATEGORIES = [
        'Création de site' => self::CATEGORIE_CREATION,
        'Abonnement' => self::CATEGORIE_ABONNEMENT,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::CATEGORIES)]
    private string $categorie = self::CATEGORIE_CREATION;

    /** Petit texte au-dessus du nom, ex. « Pour bien démarrer ». */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $surTitre = null;

    /** Pastille mise en évidence sur la carte, ex. « Notre conseil ». */
    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $badge = null;

    /** Affichée dans la sélection d'offres de la page d'accueil. */
    #[ORM\Column]
    private bool $surAccueil = false;

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

    /** Prix en euros (mention de TVA : config/packages/site.yaml). Laisser vide pour "sur devis". */
    #[ORM\Column(name: 'prix_a_partir_de', nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $prixAPartirDe = null;

    /** Affiche « À partir de » devant le prix (prix indicatif) ; sinon prix fixe. */
    #[ORM\Column]
    private bool $aPartirDe = false;

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

    /** Version texte du prix (back-office, données structurées). Ex. « À partir de 900 € », « 15 € /mois ». */
    public function getPrixAffiche(): string
    {
        if (null === $this->prixAPartirDe) {
            return 'Sur devis';
        }

        return trim(sprintf(
            '%s%s €%s',
            $this->aPartirDe ? 'À partir de ' : '',
            number_format($this->prixAPartirDe, 0, ',', ' '),
            $this->prixSuffixe ? ' '.$this->prixSuffixe : '',
        ));
    }

    public function isAbonnement(): bool
    {
        return self::CATEGORIE_ABONNEMENT === $this->categorie;
    }

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getSurTitre(): ?string
    {
        return $this->surTitre;
    }

    public function setSurTitre(?string $surTitre): static
    {
        $this->surTitre = $surTitre;

        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function setBadge(?string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function isSurAccueil(): bool
    {
        return $this->surAccueil;
    }

    public function setSurAccueil(bool $surAccueil): static
    {
        $this->surAccueil = $surAccueil;

        return $this;
    }

    public function isAPartirDe(): bool
    {
        return $this->aPartirDe;
    }

    public function setAPartirDe(bool $aPartirDe): static
    {
        $this->aPartirDe = $aPartirDe;

        return $this;
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
