<?php

namespace App\Tests\Service;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductServiceTest extends TestCase
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

    public function testGetAllProducts(): void
    {
        // Arrange
        $products = [
            $this->createMock(PhysicalProduct::class),
            $this->createMock(DigitalProduct::class),
        ];
        
        $this->productRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($products);
        
        // Act
        $result = $this->productService->getAllProducts();
        
        // Assert
        $this->assertSame($products, $result);
    }

    public function testGetProductById(): void
    {
        // Arrange
        $product = $this->createMock(PhysicalProduct::class);
        
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);
        
        // Act
        $result = $this->productService->getProductById(1);
        
        // Assert
        $this->assertSame($product, $result);
    }

    public function testGetProductByIdThrowsNotFoundException(): void
    {
        // Arrange
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);
        
        // Assert
        $this->expectException(NotFoundHttpException::class);
        
        // Act
        $this->productService->getProductById(999);
    }

    public function testCreatePhysicalProduct(): void
    {
        // Arrange
        $data = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'sku' => 'TEST-123',
            'weight' => 2.5,
        ];
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($product) use ($data) {
                return $product instanceof PhysicalProduct
                    && $product->getName() === $data['name']
                    && $product->getDescription() === $data['description']
                    && $product->getPrice() === $data['price']
                    && $product->getSku() === $data['sku']
                    && $product->getWeight() === $data['weight'];
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
        $this->assertEquals($data['sku'], $product->getSku());
        $this->assertEquals($data['weight'], $product->getWeight());
    }

    public function testCreateDigitalProduct(): void
    {
        // Arrange
        $data = [
            'type' => 'digital',
            'name' => 'Test Digital Product',
            'description' => 'Test Digital Description',
            'price' => 9.99,
            'download_url' => 'https://example.com/download',
            'file_size' => 1024,
        ];
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($product) use ($data) {
                return $product instanceof DigitalProduct
                    && $product->getName() === $data['name']
                    && $product->getDescription() === $data['description']
                    && $product->getPrice() === $data['price']
                    && $product->getDownloadUrl() === $data['download_url']
                    && $product->getFileSize() === $data['file_size'];
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
        $this->assertEquals($data['download_url'], $product->getDownloadUrl());
        $this->assertEquals($data['file_size'], $product->getFileSize());
    }

    public function testCreateProductWithInvalidType(): void
    {
        // Arrange
        $data = [
            'type' => 'invalid',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
        ];
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid product type');
        
        // Act
        $this->productService->createProduct($data);
    }
    
    public function testCreateProductWithMissingType(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
        ];
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid product type');
        
        // Act
        $this->productService->createProduct($data);
    }

    public function testUpdatePhysicalProduct(): void
    {
        // Arrange
        $productId = 1;
        $productData = [
            'name' => 'Updated Product',
            'price' => 29.99,
            'sku' => 'NEW-SKU',
            'weight' => 3.5,
        ];
        
        // Create a stub for PhysicalProduct with all methods we need
        $existingProduct = $this->getMockBuilder(PhysicalProduct::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getName', 'getDescription', 'getPrice', 'getSku', 'getWeight',
                'setName', 'setDescription', 'setPrice', 'setSku', 'setWeight'])
            ->getMock();
        
        $existingProduct->method('getName')->willReturn('Original Name');
        $existingProduct->method('getDescription')->willReturn('Original Description');
        $existingProduct->method('getPrice')->willReturn(19.99);
        $existingProduct->method('getSku')->willReturn('OLD-SKU');
        $existingProduct->method('getWeight')->willReturn(2.0);
        
        $existingProduct->expects($this->once())->method('setName')->with($productData['name']);
        $existingProduct->expects($this->once())->method('setPrice')->with($productData['price']);
        $existingProduct->expects($this->once())->method('setSku')->with($productData['sku']);
        $existingProduct->expects($this->once())->method('setWeight')->with($productData['weight']);
        $existingProduct->expects($this->never())->method('setDescription');
        
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($existingProduct);
        
        $this->entityManager->expects($this->once())->method('flush');
        
        // Act
        $result = $this->productService->updateProduct($productId, $productData);
        
        // Assert
        $this->assertSame($existingProduct, $result);
    }

    public function testUpdateDigitalProduct(): void
    {
        // Arrange
        $productId = 2;
        $productData = [
            'name' => 'Updated Digital Product',
            'description' => 'Updated Description',
            'download_url' => 'https://example.com/newdownload',
        ];
        
        // Create a stub for DigitalProduct with all methods we need
        $existingProduct = $this->getMockBuilder(DigitalProduct::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getName', 'getDescription', 'getPrice', 'getDownloadUrl', 'getFileSize',
                'setName', 'setDescription', 'setPrice', 'setDownloadUrl', 'setFileSize'])
            ->getMock();
        
        $existingProduct->method('getName')->willReturn('Original Digital Name');
        $existingProduct->method('getDescription')->willReturn('Original Description');
        $existingProduct->method('getPrice')->willReturn(9.99);
        $existingProduct->method('getDownloadUrl')->willReturn('https://example.com/olddownload');
        $existingProduct->method('getFileSize')->willReturn(512);
        
        $existingProduct->expects($this->once())->method('setName')->with($productData['name']);
        $existingProduct->expects($this->once())->method('setDescription')->with($productData['description']);
        $existingProduct->expects($this->never())->method('setPrice');
        $existingProduct->expects($this->once())->method('setDownloadUrl')->with($productData['download_url']);
        $existingProduct->expects($this->never())->method('setFileSize');
        
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($existingProduct);
        
        $this->entityManager->expects($this->once())->method('flush');
        
        // Act
        $result = $this->productService->updateProduct($productId, $productData);
        
        // Assert
        $this->assertSame($existingProduct, $result);
    }
    
    public function testUpdateNonExistentProduct(): void
    {
        // Arrange
        $productId = 999;
        $data = ['name' => 'Updated Product'];
        
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);
        
        // Assert
        $this->expectException(NotFoundHttpException::class);
        
        // Act
        $this->productService->updateProduct($productId, $data);
    }

    public function testDeleteProduct(): void
    {
        // Arrange
        $productId = 1;
        $product = $this->createMock(PhysicalProduct::class);
        
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($product);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // Act
        $this->productService->deleteProduct($productId);
        
        // No assertion needed as we're testing void method and verifying mocks
    }
    
    public function testDeleteNonExistentProduct(): void
    {
        // Arrange
        $productId = 999;
        
        $this->productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);
        
        // Assert
        $this->expectException(NotFoundHttpException::class);
        
        // Act
        $this->productService->deleteProduct($productId);
    }
    
    public function testGetPaginatedProducts(): void
    {
        // Arrange
        $page = 2;
        $limit = 5;
        $expectedResult = [
            'items' => [$this->createMock(PhysicalProduct::class)],
            'total' => 10,
            'page' => 2,
            'limit' => 5,
            'pages' => 2
        ];
        
        $this->productRepository->expects($this->once())
            ->method('findPaginated')
            ->with($page, $limit)
            ->willReturn($expectedResult);
        
        // Act
        $result = $this->productService->getPaginatedProducts($page, $limit);
        
        // Assert
        $this->assertSame($expectedResult, $result);
    }
    
    public function testGetPaginatedProductsWithDefaultValues(): void
    {
        // Arrange
        $expectedResult = [
            'items' => [$this->createMock(PhysicalProduct::class)],
            'total' => 10,
            'page' => 1,
            'limit' => 10,
            'pages' => 1
        ];
        
        $this->productRepository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 10)
            ->willReturn($expectedResult);
        
        // Act
        $result = $this->productService->getPaginatedProducts();
        
        // Assert
        $this->assertSame($expectedResult, $result);
    }
}
