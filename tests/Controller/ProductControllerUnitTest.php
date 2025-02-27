<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductControllerUnitTest extends TestCase
{
    private ProductService $productService;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private ProductController $controller;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        
        $this->controller = new class($this->productService, $this->serializer, $this->validator) extends ProductController {
            // Override the json method to avoid using AbstractController functionality
            public function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                return new JsonResponse($data, $status, $headers);
            }
        };
    }

    public function testList(): void
    {
        // Arrange
        $products = [
            $this->createProductMock(PhysicalProduct::class, 1),
            $this->createProductMock(DigitalProduct::class, 2),
        ];
        
        $this->productService->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);
        
        // Act
        $request = new Request();
        $response = $this->controller->list($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGet(): void
    {
        // Arrange
        $productId = 1;
        $product = $this->createProductMock(PhysicalProduct::class, $productId);
        
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willReturn($product);
        
        // Act
        $response = $this->controller->get($productId);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetThrowsNotFoundException(): void
    {
        // Arrange
        $productId = 999;
        
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willThrowException(new NotFoundHttpException("Product not found"));
        
        // Assert
        $this->expectException(NotFoundHttpException::class);
        
        // Act
        $this->controller->get($productId);
    }

    public function testCreate(): void
    {
        // Arrange
        $request = $this->createConfiguredJsonRequest([
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'sku' => 'TEST-123',
        ]);
        
        $product = $this->createProductMock(PhysicalProduct::class, 1);
        
        $this->productService->expects($this->once())
            ->method('createProduct')
            ->with($this->callback(function ($data) {
                return is_array($data)
                    && $data['type'] === 'physical'
                    && $data['name'] === 'Test Product';
            }))
            ->willReturn($product);
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testCreateWithInvalidJson(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getContent')
            ->willReturn('invalid json');
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('error', $response->getContent());
    }

    public function testUpdate(): void
    {
        // Arrange
        $productId = 1;
        $request = $this->createConfiguredJsonRequest([
            'name' => 'Updated Product',
            'price' => 29.99,
        ]);
        
        $product = $this->createProductMock(PhysicalProduct::class, $productId);
        
        $this->productService->expects($this->once())
            ->method('updateProduct')
            ->with(
                $this->equalTo($productId),
                $this->callback(function ($data) {
                    return is_array($data)
                        && $data['name'] === 'Updated Product'
                        && $data['price'] === 29.99;
                })
            )
            ->willReturn($product);
        
        // Act
        $response = $this->controller->update($productId, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDelete(): void
    {
        // Arrange
        $productId = 1;
        
        $this->productService->expects($this->once())
            ->method('deleteProduct')
            ->with($productId);
        
        // Act
        $response = $this->controller->delete($productId);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * Create a mock product of the given class with the specified ID
     */
    private function createProductMock(string $class, int $id)
    {
        $product = $this->createMock($class);
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn('Product ' . $id);
        $product->method('getDescription')->willReturn('Description ' . $id);
        $product->method('getPrice')->willReturn(19.99 + $id);
        
        if ($class === PhysicalProduct::class) {
            $product->method('getSku')->willReturn('SKU-' . $id);
            $product->method('getWeight')->willReturn(1.5 + $id);
        } elseif ($class === DigitalProduct::class) {
            $product->method('getDownloadUrl')->willReturn('https://example.com/download/' . $id);
            $product->method('getFileSize')->willReturn(1024 * $id);
        }
        
        return $product;
    }

    /**
     * Create a request mock with JSON content
     */
    private function createConfiguredJsonRequest(array $data): Request
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode($data));
        
        return $request;
    }
}
