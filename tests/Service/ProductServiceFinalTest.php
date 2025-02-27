<?php

namespace App\Tests\Service;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductServiceFinalTest extends TestCase
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

    /**
     * Test that updating a digital product correctly handles all fields
     */
    public function testUpdateDigitalProductWithAllFields(): void
    {
        // Create a digital product with initial values
        $product = new DigitalProduct();
        $product->setName('Original Name');
        $product->setDescription('Original Description');
        $product->setPrice(9.99);
        $product->setDownloadUrl('https://example.com/original');
        $product->setFileSize(1024);
        
        $productId = 1;
        
        // Setup repository to return our product
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);
        
        // Expect flush to be called
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // Data to update
        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'price' => 19.99,
            'download_url' => 'https://example.com/updated',
            'file_size' => 2048
        ];
        
        // Update the product
        $result = $this->productService->updateProduct($productId, $updateData);
        
        // Verify all fields were updated
        $this->assertEquals('Updated Name', $result->getName());
        $this->assertEquals('Updated Description', $result->getDescription());
        $this->assertEquals(19.99, $result->getPrice());
        $this->assertEquals('https://example.com/updated', $result->getDownloadUrl());
        $this->assertEquals(2048, $result->getFileSize());
    }
}
