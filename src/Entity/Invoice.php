<?php
namespace App\Entity;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $folio = null;

    #[ORM\Column(length: 255)]
    private ?string $emisor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receptor = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $periodo = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column(length: 20)]
    private ?string $estado = 'PENDIENTE';

    public function getId(): ?int { return $this->id; }
    public function getFolio(): ?string { return $this->folio; }
    public function setFolio(string $folio): static { $this->folio = $folio; return $this; }
    public function getEmisor(): ?string { return $this->emisor; }
    public function setEmisor(string $emisor): static { $this->emisor = $emisor; return $this; }
    public function getReceptor(): ?string { return $this->receptor; }
    public function setReceptor(?string $receptor): static { $this->receptor = $receptor; return $this; }
    public function getPeriodo(): ?string { return $this->periodo; }
    public function setPeriodo(?string $periodo): static { $this->periodo = $periodo; return $this; }
    public function getTotal(): ?float { return $this->total; }
    public function setTotal(float $total): static { $this->total = $total; return $this; }
    public function getEstado(): ?string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
}
