<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La empresa es obligatoria')]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'El propietario es obligatorio')]
    private ?Owner $owner = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'La dirección es obligatoria')]
    private ?string $direccion = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rol = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $comuna = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ciudad = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): static { $this->company = $company; return $this; }
    public function getOwner(): ?Owner { return $this->owner; }
    public function setOwner(?Owner $owner): static { $this->owner = $owner; return $this; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(string $direccion): static { $this->direccion = $direccion; return $this; }
    public function getRol(): ?string { return $this->rol; }
    public function setRol(?string $rol): static { $this->rol = $rol; return $this; }
    public function getComuna(): ?string { return $this->comuna; }
    public function setComuna(?string $comuna): static { $this->comuna = $comuna; return $this; }
    public function getCiudad(): ?string { return $this->ciudad; }
    public function setCiudad(?string $ciudad): static { $this->ciudad = $ciudad; return $this; }
    public function getDeletedAt(): ?\DateTimeInterface { return $this->deletedAt; }
    public function setDeletedAt(?\DateTimeInterface $deletedAt): static { $this->deletedAt = $deletedAt; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    
    public function isDeleted(): bool { return $this->deletedAt !== null; }
    public function softDelete(): void { $this->deletedAt = new \DateTime(); }
}