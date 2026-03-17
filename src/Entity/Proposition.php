<?php

namespace App\Entity;

use App\Repository\PropositionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropositionRepository::class)]
class Proposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $auteur;

    #[ORM\Column(length: 200)]
    private string $titre = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $contenu = '';

    #[ORM\Column]
    private bool $estLuParAdmin = false;

    #[ORM\Column]
    private \DateTimeImmutable $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuteur(): User
    {
        return $this->auteur;
    }

    public function setAuteur(User $auteur): self
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function isEstLuParAdmin(): bool
    {
        return $this->estLuParAdmin;
    }

    public function setEstLuParAdmin(bool $estLuParAdmin): self
    {
        $this->estLuParAdmin = $estLuParAdmin;

        return $this;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
