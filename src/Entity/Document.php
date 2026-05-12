<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    public const TYPE_PLANNING = 'planning';
    public const TYPE_REPORT = 'report';
    public const TYPE_MOOK = 'mook';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $type = '';

    #[ORM\Column(length: 200)]
    private string $label = '';

    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[ORM\Column]
    private \DateTimeImmutable $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function getPublicPath(): string
    {
        return match ($this->type) {
            self::TYPE_PLANNING => '/uploads/planning/' . $this->filename,
            self::TYPE_REPORT => '/uploads/comptes-rendus/' . $this->filename,
            self::TYPE_MOOK => '/uploads/mooks/' . $this->filename,
            default => '/uploads/' . $this->filename,
        };
    }

    public function getUploadSubdir(): string
    {
        return match ($this->type) {
            self::TYPE_PLANNING => 'planning',
            self::TYPE_REPORT => 'comptes-rendus',
            self::TYPE_MOOK => 'mooks',
            default => '',
        };
    }
}
