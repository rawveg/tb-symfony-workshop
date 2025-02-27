<?php

namespace App\Tests\Service;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductServiceExtendedTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        
        $this->productService = new ProductService(
            $this->entityManager,
            $this->productRepository
        );
    }
    
    public function testCreateProductWithPartialPhysicalData(): void
    {
        // Arrange - minimal required data for a physical product
        $data = [
            'type' => 'physical',
            'name' => 'Minimal Physical Product',
            'description' => 'Minimal Description',
            'price' => 19.99,
            // No SKU or weight
        ];
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (PhysicalProduct $product) use ($data) {
                return $product->getName() === $data['name'] &&
                       $product->getDescription() === $data['description'] &&
                       $product->getPrice() === $data['price'] &&
                       $product->getSku() === null &&
                       $product->getWeight() === null;
            }));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // Act
        $product = $this->productService->createProduct($data);
        
        // Assert
        $this->assertInstanceOf(PhysicalProduct::class, $product);
        $this->assertEquals($data['name'], $product->getName());
        $this->assertEquals($data['description'], $product->getDescription());
        $this->assertEquals($data['price'], $product->getPrice());
        $this->assertNull($product->getSku());
        $this->assertNull($product->getWeight());
    }
    
    public function testCreateProductWithPartialDigitalData(): void
    {
        // Arrange - minimal required data for a digital product
        $data = [
            'type' => 'digital',
            'name' => 'Minimal Digital Product',
            'description' => 'Minimal Description',
            'price' => 9.99,
            // No download_url or file_size
        ];
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DigitalProduct $product) use ($data) {
                return $product->getName() === $data['name'] &&
                       $product->getDescription() === $data['description'] &&
                       $product->getPrice() === $data['price'] &&
                       $product->getDownloadUrl() === null &&
                       $product->getFileSize() === null;
            }));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // Act
        $product = $this->productService->createProduct($data);
        
        // Assert
        $this->assertInstanceOf(DigitalProduct::class, $product);
        $this->assertEquals($data['name'], $product->getName());
        $this->assertEquals($data['description'], $product->getDescription());
        $this->assertEquals($data['price'], $product->getPrice());
        $this->assertNull($product->getDownloadUrl());
        $this->assertNull($product->getFileSize());
    }
}
