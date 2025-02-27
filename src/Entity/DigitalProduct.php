<?php

namespace App\Entity;

use App\Repository\DigitalProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DigitalProductRepository::class)]
#[ORM\Table(name: 'digital_products')]
class DigitalProduct extends Product
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    #[Groups(['product:read', 'product:write'])]
    private ?string $downloadUrl = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['product:read', 'product:write'])]
    private ?int $fileSize = null;

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): static
    {
        $this->downloadUrl = $downloadUrl;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }
    
    public function getType(): string
    {
        return 'digital';
    }
}
