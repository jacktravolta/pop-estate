<?php

namespace App\Entity;

use App\Repository\OwnerRepository;
use App\Validator\Rut;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OwnerRepository::class)]
class Owner
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
    #[Assert\NotBlank(message: 'El nombre es obligatorio')]
    private ?string $nombre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $giro = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $comuna = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ciudad = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'Email inválido')]
    private ?string $email = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getRut(): ?string { return $this->rut; }
    public function setRut(string $rut): static { $this->rut = $rut; return $this; }
    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }
    public function getGiro(): ?string { return $this->giro; }
    public function setGiro(?string $giro): static { $this->giro = $giro; return $this; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $direccion): static { $this->direccion = $direccion; return $this; }
    public function getComuna(): ?string { return $this->comuna; }
    public function setComuna(?string $comuna): static { $this->comuna = $comuna; return $this; }
    public function getCiudad(): ?string { return $this->ciudad; }
    public function setCiudad(?string $ciudad): static { $this->ciudad = $ciudad; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}