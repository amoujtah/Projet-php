<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\ManyToMany(targetEntity: Plat::class, inversedBy: 'menus')]
    private Collection $plats;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prixTotal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column]
    private ?bool $disponible = true;

    public function __construct()
    {
        $this->plats = new ArrayCollection();
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

    /**
     * @return Collection<int, Plat>
     */
    public function getPlats(): Collection
    {
        return $this->plats;
    }

    public function addPlat(Plat $plat): static
    {
        if (!$this->plats->contains($plat)) {
            $this->plats->add($plat);
        }
        return $this;
    }

    public function removePlat(Plat $plat): static
    {
        $this->plats->removeElement($plat);
        return $this;
    }

    public function getPrixTotal(): ?string
    {
        return $this->prixTotal;
    }

    // CORRECTION : Accepte null comme paramètre
    public function setPrixTotal(?string $prixTotal): static
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    // MÉTHODE ALTERNATIVE : Convertit float en string
    public function setPrixTotalFloat(?float $prixTotal): static
    {
        $this->prixTotal = $prixTotal !== null ? number_format($prixTotal, 2, '.', '') : null;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function isDisponible(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;
        return $this;
    }

    // MÉTHODE UTILE POUR LE TEMPLATE
    public function getPrix(): ?string
    {
        return $this->prixTotal;
    }

    public function calculerPrixTotal(): float
    {
        $total = 0;
        foreach ($this->plats as $plat) {
            $total += (float) $plat->getPrix();
        }
        return $total;
    }

    public function getPrixFormate(): string
    {
        if ($this->prixTotal === null) {
            return '0.00 €';
        }
        return number_format((float) $this->prixTotal, 2, ',', ' ') . ' €';
    }

    public function __toString(): string
    {
        return $this->nom . ' (' . ($this->prixTotal ?? '0.00') . ' €)';
    }

    // MÉTHODES UTILES POUR LA VALIDATION
    public function isValid(): bool
    {
        return !empty($this->nom) && $this->prixTotal !== null;
    }

    public function getPrixAsFloat(): ?float
    {
        return $this->prixTotal !== null ? (float) $this->prixTotal : null;
    }
}