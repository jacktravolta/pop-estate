<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use App\Validator\Rut;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12, unique: true)]
    #[Assert\NotBlank(message: 'El RUT es obligatorio')]
    #[Rut]
    private ?string $rut = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'La razón social es obligatoria')]
    private ?string $razonSocial = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $giro = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'La dirección es obligatoria')]
    private ?string $direccion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $comuna = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ciudad = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'Email inválido')]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getRut(): ?string { return $this->rut; }
    public function setRut(string $rut): static { $this->rut = $rut; return $this; }
    public function getRazonSocial(): ?string { return $this->razonSocial; }
    public function setRazonSocial(string $razonSocial): static { $this->razonSocial = $razonSocial; return $this; }
    public function getGiro(): ?string { return $this->giro; }
    public function setGiro(?string $giro): static { $this->giro = $giro; return $this; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(string $direccion): static { $this->direccion = $direccion; return $this; }
    public function getComuna(): ?string { return $this->comuna; }
    public function setComuna(?string $comuna): static { $this->comuna = $comuna; return $this; }
    public function getCiudad(): ?string { return $this->ciudad; }
    public function setCiudad(?string $ciudad): static { $this->ciudad = $ciudad; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getDeletedAt(): ?\DateTimeInterface { return $this->deletedAt; }
    public function setDeletedAt(?\DateTimeInterface $deletedAt): static { $this->deletedAt = $deletedAt; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
    
    public function isDeleted(): bool { return $this->deletedAt !== null; }
    public function softDelete(): void { $this->deletedAt = new \DateTime(); }
}