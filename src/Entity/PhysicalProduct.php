<?php

namespace App\Entity;

use App\Repository\PhysicalProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhysicalProductRepository::class)]
#[ORM\Table(name: 'physical_products')]
class PhysicalProduct extends Product
{
    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $sku = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['product:read', 'product:write'])]
    private ?float $weight = null;

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }
    
    public function getType(): string
    {
        return 'physical';
    }
}
