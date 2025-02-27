<?php

namespace App\Serializer;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProductNormalizer implements NormalizerInterface, SerializerAwareInterface, CacheableSupportsMethodInterface
{
    private SerializerInterface $serializer;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Product $object
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'price' => $object->getPrice(),
            'type' => $object->getType(),
        ];

        if ($object instanceof PhysicalProduct) {
            $data['sku'] = $object->getSku();
            $data['weight'] = $object->getWeight();
        } elseif ($object instanceof DigitalProduct) {
            $data['download_url'] = $object->getDownloadUrl();
            $data['file_size'] = $object->getFileSize();
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Product;
    }
    
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
