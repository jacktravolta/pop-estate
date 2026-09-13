<?php
namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(length: 50)]
    private ?string $sourceType = null;

    #[ORM\Column]
    private ?int $sourceId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function setMetadata(?array $metadata): static { $this->metadata = $metadata; return $this; }
    public function getSourceType(): ?string { return $this->sourceType; }
    public function setSourceType(string $sourceType): static { $this->sourceType = $sourceType; return $this; }
    public function getSourceId(): ?int { return $this->sourceId; }
    public function setSourceId(int $sourceId): static { $this->sourceId = $sourceId; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}