<?php

namespace App\Tests\Serializer;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Serializer\ProductNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class ProductNormalizerTest extends TestCase
{
    private ProductNormalizer $normalizer;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->normalizer = new ProductNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    public function testNormalizePhysicalProduct(): void
    {
        // Create a mock of a PhysicalProduct
        $product = $this->createMock(PhysicalProduct::class);
        $product->method('getId')->willReturn(1);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getDescription')->willReturn('Test Description');
        $product->method('getPrice')->willReturn(19.99);
        $product->method('getType')->willReturn('physical');
        $product->method('getSku')->willReturn('TEST-123');
        $product->method('getWeight')->willReturn(1.5);
        
        // Normalize the product
        $normalized = $this->normalizer->normalize($product);
        
        // Assert the normalized data is correct
        $this->assertIsArray($normalized);
        $this->assertEquals(1, $normalized['id']);
        $this->assertEquals('Test Product', $normalized['name']);
        $this->assertEquals('Test Description', $normalized['description']);
        $this->assertEquals(19.99, $normalized['price']);
        $this->assertEquals('physical', $normalized['type']);
        $this->assertEquals('TEST-123', $normalized['sku']);
        $this->assertEquals(1.5, $normalized['weight']);
    }
    
    public function testNormalizeDigitalProduct(): void
    {
        // Create a mock of a DigitalProduct
        $product = $this->createMock(DigitalProduct::class);
        $product->method('getId')->willReturn(2);
        $product->method('getName')->willReturn('Digital Product');
        $product->method('getDescription')->willReturn('Digital Description');
        $product->method('getPrice')->willReturn(9.99);
        $product->method('getType')->willReturn('digital');
        $product->method('getDownloadUrl')->willReturn('https://example.com/download');
        $product->method('getFileSize')->willReturn(1024);
        
        // Normalize the product
        $normalized = $this->normalizer->normalize($product);
        
        // Assert the normalized data is correct
        $this->assertIsArray($normalized);
        $this->assertEquals(2, $normalized['id']);
        $this->assertEquals('Digital Product', $normalized['name']);
        $this->assertEquals('Digital Description', $normalized['description']);
        $this->assertEquals(9.99, $normalized['price']);
        $this->assertEquals('digital', $normalized['type']);
        $this->assertEquals('https://example.com/download', $normalized['download_url']);
        $this->assertEquals(1024, $normalized['file_size']);
    }
    
    public function testSupportsNormalization(): void
    {
        // Test with a Product
        $product = $this->createMock(PhysicalProduct::class);
        $this->assertTrue($this->normalizer->supportsNormalization($product));
        
        // Test with a DigitalProduct
        $digitalProduct = $this->createMock(DigitalProduct::class);
        $this->assertTrue($this->normalizer->supportsNormalization($digitalProduct));
        
        // Test with a non-Product
        $notProduct = new \stdClass();
        $this->assertFalse($this->normalizer->supportsNormalization($notProduct));
    }
    
    public function testHasCacheableSupportsMethod(): void
    {
        $this->assertTrue($this->normalizer->hasCacheableSupportsMethod());
    }
}
