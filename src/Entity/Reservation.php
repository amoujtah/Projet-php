<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RestaurantTable $restaurantTable = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null; // Contient à la fois la date ET l'heure

    #[ORM\Column]
    private ?int $nbPersonnes = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getClient(): ?User
    {
        return $this->client;
    }
    
    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }
    
    public function getRestaurantTable(): ?RestaurantTable
    {
        return $this->restaurantTable;
    }
    
    public function setRestaurantTable(?RestaurantTable $restaurantTable): static
    {
        $this->restaurantTable = $restaurantTable;
        return $this;
    }
    
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
    
    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }
    
    public function getNbPersonnes(): ?int
    {
        return $this->nbPersonnes;
    }
    
    public function setNbPersonnes(?int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;
        return $this;
    }
    
    public function getStatus(): ?string
    {
        return $this->status;
    }
    
    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }
    
    // ============ MÉTHODES HELPER POUR TRAVAILLER AVEC L'HEURE ============
    
    /**
     * Retourne uniquement la partie heure de la date
     */
    public function getHeure(): ?\DateTimeInterface
    {
        if (!$this->date) {
            return null;
        }
        
        // Crée un objet DateTime avec seulement l'heure
        return \DateTime::createFromFormat('H:i:s', $this->date->format('H:i:s'));
    }
    
    /**
     * Retourne l'heure formatée (HH:MM)
     */
    public function getHeureFormat(): ?string
    {
        if (!$this->date) {
            return null;
        }
        
        return $this->date->format('H:i');
    }
    
    /**
     * Retourne la date formatée (YYYY-MM-DD)
     */
    public function getDateOnlyFormat(): ?string
    {
        if (!$this->date) {
            return null;
        }
        
        return $this->date->format('Y-m-d');
    }
    
    /**
     * Définit l'heure en conservant la date existante
     */
    public function setHeure(?\DateTimeInterface $heure): static
    {
        if ($this->date && $heure) {
            // Conserve la date existante, ne change que l'heure
            $this->date->setTime(
                (int) $heure->format('H'),
                (int) $heure->format('i'),
                (int) $heure->format('s')
            );
        }
        
        return $this;
    }
    
    /**
     * Méthode pour définir date et heure séparément
     */
    public function setDateAndTime(?\DateTimeInterface $date, ?\DateTimeInterface $heure): static
    {
        if ($date && $heure) {
            $combined = clone $date;
            $combined->setTime(
                (int) $heure->format('H'),
                (int) $heure->format('i'),
                (int) $heure->format('s')
            );
            $this->date = $combined;
        }
        
        return $this;
    }
    
    /**
     * Retourne la date et l'heure formatées
     */
    public function getDateTimeFormat(): ?string
    {
        if (!$this->date) {
            return null;
        }
        
        return $this->date->format('d/m/Y H:i');
    }
}