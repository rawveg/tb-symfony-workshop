<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\PhysicalProduct;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductControllerTest extends TestCase
{
    private const API_KEY = 'workshop_secret_api_key';
    
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
            // Override the json method to make it testable without the parent class behavior
            public function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                return new JsonResponse($data, $status, $headers);
            }
            
            // Use reflection to access private properties for testing
            public function getInjectedDependencies(): array
            {
                // Reflection allows us to access private properties
                $reflection = new \ReflectionClass($this);
                
                // We need to get the properties from the parent class
                $productServiceProp = $reflection->getParentClass()->getProperty('productService');
                $serializerProp = $reflection->getParentClass()->getProperty('serializer');
                $validatorProp = $reflection->getParentClass()->getProperty('validator');
                
                // Make them accessible
                $productServiceProp->setAccessible(true);
                $serializerProp->setAccessible(true);
                $validatorProp->setAccessible(true);
                
                // Return all dependencies
                return [
                    'productService' => $productServiceProp->getValue($this),
                    'serializer' => $serializerProp->getValue($this),
                    'validator' => $validatorProp->getValue($this)
                ];
            }
        };
    }
    
    public function testControllerConstructor(): void
    {
        // Verify injected dependencies are correctly stored
        $dependencies = $this->controller->getInjectedDependencies();
        
        $this->assertSame($this->productService, $dependencies['productService']);
        $this->assertSame($this->serializer, $dependencies['serializer']);
        $this->assertSame($this->validator, $dependencies['validator']);
    }
    
    public function testControllerJsonMethod(): void
    {
        // Test the json method with various inputs
        $data = ['test' => 'value'];
        $response = $this->controller->json($data, 201, ['X-Test' => 'Header']);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Header', $response->headers->get('X-Test'));
        $this->assertStringContainsString('"test":"value"', $response->getContent());
    }
    
    public function testProductListEndpoint(): void
    {
        // Arrange
        $products = [
            $this->createProductMock(1),
            $this->createProductMock(2),
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
    
    public function testProductListWithPaginationEndpoint(): void
    {
        // Arrange
        $paginatedResult = [
            'items' => [
                $this->createProductMock(1),
                $this->createProductMock(2),
            ],
            'total' => 10,
            'page' => 1,
            'limit' => 2,
            'pages' => 5
        ];
        
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(1, 2)
            ->willReturn($paginatedResult);
        
        // Act
        $request = new Request([ 'page' => '1', 'limit' => '2' ]);
        $response = $this->controller->list($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testCreateProductEndpoint(): void
    {
        // Arrange
        $productData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'sku' => 'TEST-123',
            'weight' => 2.5,
        ];
        
        $request = $this->createJsonRequest(json_encode($productData));
        $product = $this->createProductMock(1);
        
        $this->productService->expects($this->once())
            ->method('createProduct')
            ->with($this->callback(function ($data) use ($productData) {
                return $data['name'] === $productData['name'] &&
                       $data['price'] === $productData['price'];
            }))
            ->willReturn($product);
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }
    
    public function testCreateProductWithServiceException(): void
    {
        // Arrange
        $productData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
        ];
        
        $request = $this->createJsonRequest(json_encode($productData));
        
        // Setup the service to throw an exception
        $this->productService->expects($this->once())
            ->method('createProduct')
            ->willThrowException(new \InvalidArgumentException('Invalid product data'));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid product data', $response->getContent());
    }
    
    public function testGetProductEndpoint(): void
    {
        // Arrange
        $productId = 1;
        $product = $this->createProductMock($productId);
        
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
    
    public function testGetProductNotFound(): void
    {
        // Arrange
        $productId = 999; // Non-existent ID
        
        // Setup service to throw exception
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willThrowException(new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Product not found'));
            
        // Act and Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->controller->get($productId);
    }
    
    public function testUpdateProductEndpoint(): void
    {
        // Arrange
        $productId = 1;
        $productData = [
            'name' => 'Updated Product',
            'price' => 29.99,
        ];
        
        $request = $this->createJsonRequest(json_encode($productData));
        $product = $this->createProductMock($productId);
        
        $this->productService->expects($this->once())
            ->method('updateProduct')
            ->with(
                $this->equalTo($productId),
                $this->callback(function ($data) use ($productData) {
                    return $data['name'] === $productData['name'] &&
                           $data['price'] === $productData['price'];
                })
            )
            ->willReturn($product);
        
        // Act
        $response = $this->controller->update($productId, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testDeleteProductEndpoint(): void
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
    
    public function testMissingApiKey(): void
    {
        // This test is now covered in ApiKeyAuthenticationTest
        $this->assertTrue(true);
    }
    
    public function testPaginationWithInvalidParameters(): void
    {
        // Arrange
        $request = new Request(['page' => '-1', 'limit' => '-5']);
        
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(1, 1) // Should be normalized to minimum values
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'limit' => 1,
                'pages' => 0
            ]);
        
        // Act
        $response = $this->controller->list($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testPaginationWithTooLargeLimit(): void
    {
        // Arrange
        $request = new Request(['page' => '1', 'limit' => '1000']);
        
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(1, 50) // Should be capped at 50
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'limit' => 50,
                'pages' => 0
            ]);
        
        // Act
        $response = $this->controller->list($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testCreateProductWithInvalidJson(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn('invalid-json');
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid JSON', $response->getContent());
    }
    
    public function testUpdateProductWithServiceException(): void 
    {
        // Arrange - create data for updating
        $productId = 1;
        $updateData = [
            'name' => 'Updated Product',
            'price' => 29.99
        ];
        
        $request = $this->createJsonRequest(json_encode($updateData));
        
        // Setup the mock to return an existing product
        $product = $this->createProductMock($productId);
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willReturn($product);
            
        // Setup the mock to throw an exception during update
        $this->productService->expects($this->once())
            ->method('updateProduct')
            ->willThrowException(new \InvalidArgumentException('Test exception'));
        
        // Act
        $response = $this->controller->update($productId, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Test exception', $response->getContent());
    }
    
    public function testCreateProductWithMissingRequiredFields(): void
    {
        // Arrange
        $incompleteData = [
            'type' => 'physical',
            // Missing name, description, price
        ];
        
        $request = $this->createJsonRequest(json_encode($incompleteData));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Missing required fields', $response->getContent());
    }
    
    public function testCreateProductWithInvalidType(): void
    {
        // Arrange
        $invalidData = [
            'type' => 'invalid-type',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid product type', $response->getContent());
    }
    
    public function testCreateProductWithInvalidPrice(): void
    {
        // Arrange
        $invalidData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => -10 // Invalid negative price
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid price', $response->getContent());
    }
    
    public function testCreateProductWithInvalidPhysicalAttributes(): void
    {
        // Arrange
        $invalidData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'weight' => -5 // Invalid negative weight
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid weight', $response->getContent());
    }
    
    public function testCreateDigitalProductWithInvalidUrl(): void
    {
        // Arrange
        $invalidData = [
            'type' => 'digital',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'download_url' => 'not-a-valid-url'
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid download URL', $response->getContent());
    }
    
    public function testCreateDigitalProductWithInvalidFileSize(): void
    {
        // Arrange
        $invalidData = [
            'type' => 'digital',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'file_size' => -100 // Invalid negative file size
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Act
        $response = $this->controller->create($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid file size', $response->getContent());
    }
    
    public function testUpdateProductWithInvalidJson(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn('invalid-json');
        
        // Act
        $response = $this->controller->update(1, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid JSON', $response->getContent());
    }
    
    public function testUpdateProductWithInvalidPrice(): void
    {
        // Arrange - note that we don't need to setup getProductById since
        // the validation fails before that method is called
        $invalidData = [
            'price' => -10 // Invalid negative price
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Act
        $response = $this->controller->update(1, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid price', $response->getContent());
    }
    
    public function testUpdatePhysicalProductWithInvalidWeight(): void
    {
        // Arrange
        $invalidData = [
            'weight' => -5 // Invalid negative weight
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Set up the mock product to return
        $product = $this->createMock(PhysicalProduct::class);
        $product->method('getType')->willReturn('physical');
        
        // Set expectation for getting product
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(1)
            ->willReturn($product);
        
        // Act
        $response = $this->controller->update(1, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid weight', $response->getContent());
    }
    
    public function testUpdateDigitalProductWithInvalidUrl(): void
    {
        // Arrange
        $invalidData = [
            'download_url' => 'not-a-valid-url' // Invalid URL
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Set up the mock product to return
        $product = $this->createMock(PhysicalProduct::class);
        $product->method('getType')->willReturn('digital');
        
        // Set expectation for getting product
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(1)
            ->willReturn($product);
        
        // Act
        $response = $this->controller->update(1, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid download URL', $response->getContent());
    }
    
    public function testUpdateDigitalProductWithInvalidFileSize(): void
    {
        // Arrange
        $invalidData = [
            'file_size' => -100 // Invalid negative file size
        ];
        
        $request = $this->createJsonRequest(json_encode($invalidData));
        
        // Set up the mock product to return
        $product = $this->createMock(PhysicalProduct::class);
        $product->method('getType')->willReturn('digital');
        
        // Set expectation for getting product
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(1)
            ->willReturn($product);
        
        // Act
        $response = $this->controller->update(1, $request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid file size', $response->getContent());
    }
    
    private function createProductMock(int $id)
    {
        $product = $this->createMock(PhysicalProduct::class);
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn('Product ' . $id);
        $product->method('getDescription')->willReturn('Description ' . $id);
        $product->method('getPrice')->willReturn(19.99 + $id);
        
        return $product;
    }
    
    private function createJsonRequest(string $content): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn($content);
        
        return $request;
    }
}
