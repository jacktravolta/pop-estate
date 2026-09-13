<?php

namespace App\Entity;

use App\Repository\SettlementItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SettlementItemRepository::class)]
class SettlementItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Settlement $settlement = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['CARGO', 'DESCUENTO'], message: 'Tipo inválido')]
    private ?string $tipo = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'La descripción es obligatoria')]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\NotNull(message: 'El monto es obligatorio')]
    #[Assert\GreaterThanOrEqual(0, message: 'El monto debe ser mayor o igual a 0')]
    private string $monto = '0.00';

    public function getId(): ?int { return $this->id; }
    public function getSettlement(): ?Settlement { return $this->settlement; }
    public function setSettlement(?Settlement $settlement): static { $this->settlement = $settlement; return $this; }
    public function getTipo(): ?string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(string $descripcion): static { $this->descripcion = $descripcion; return $this; }
    public function getMonto(): string { return $this->monto; }
    public function setMonto(string $monto): static { $this->monto = $monto; return $this; }
}