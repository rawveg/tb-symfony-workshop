<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Extended tests for ProductController to achieve 100% coverage
 * @covers \App\Controller\ProductController
 */
class ExtendedProductControllerTest extends TestCase
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
        
        $this->controller = new ProductController(
            $this->productService,
            $this->serializer,
            $this->validator
        );
    }
    
    public function testListWithoutPagination(): void
    {
        // Setup test data
        $products = [$this->createMock(Product::class)];
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);
            
        // Create a mock request without pagination params
        $request = new Request();
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'list', [$request]);
        
        // Assertions
        $this->assertEquals(200, $result['status']);
        $this->assertSame($products, $result['data']);
    }
    
    public function testListWithPagination(): void
    {
        // Setup test data
        $paginatedResult = [
            'items' => [$this->createMock(Product::class)],
            'total' => 10,
            'page' => 2,
            'limit' => 5,
            'pages' => 2
        ];
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(2, 5)
            ->willReturn($paginatedResult);
            
        // Create a mock request with pagination params
        $request = new Request(['page' => '2', 'limit' => '5']);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'list', [$request]);
        
        // Assertions
        $this->assertEquals(200, $result['status']);
        $this->assertSame($paginatedResult, $result['data']);
    }
    
    public function testGetProduct(): void
    {
        // Setup test data
        $product = $this->createMock(Product::class);
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(123)
            ->willReturn($product);
            
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'get', [123]);
        
        // Assertions
        $this->assertEquals(200, $result['status']);
        $this->assertSame($product, $result['data']);
    }
    
    public function testGetProductNotFound(): void
    {
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(999)
            ->willThrowException(new NotFoundHttpException('Product not found'));
            
        // Expect exception
        $this->expectException(NotFoundHttpException::class);
        
        // Call method to trigger exception
        $this->callProtectedJsonMethod($this->controller, 'get', [999]);
    }
    
    public function testCreateProduct(): void
    {
        // Setup test data
        $requestData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99
        ];
        
        $product = $this->createMock(Product::class);
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('createProduct')
            ->with($requestData)
            ->willReturn($product);
            
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(201, $result['status']);
        $this->assertSame($product, $result['data']);
    }
    
    public function testCreateInvalidJson(): void
    {
        // Setup invalid JSON request
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn('invalid-json');
            
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid JSON', $result['data']['error']);
    }
    
    public function testCreateWithMissingRequiredFields(): void
    {
        // Setup incomplete data
        $requestData = [
            'type' => 'physical',
            // Missing name, description, price
        ];
        
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Missing required fields', $result['data']['error']);
        $this->assertArrayHasKey('fields', $result['data']);
    }
    
    public function testCreateWithInvalidType(): void
    {
        // Setup data with invalid type
        $requestData = [
            'type' => 'invalid-type',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99
        ];
        
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid product type', $result['data']['error']);
    }
    
    public function testCreateWithInvalidPrice(): void
    {
        // Setup data with invalid price
        $requestData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => -10 // Invalid negative price
        ];
        
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid price', $result['data']['error']);
    }
    
    public function testCreateWithInvalidPhysicalAttributes(): void
    {
        // Setup physical product with invalid weight
        $requestData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'weight' => -5 // Invalid negative weight
        ];
        
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid weight', $result['data']['error']);
    }
    
    public function testCreateDigitalProductWithInvalidAttributes(): void
    {
        // Setup digital product with invalid URL and file size
        $requestData = [
            'type' => 'digital',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'download_url' => 'not-a-valid-url',
            'file_size' => -100 // Invalid negative file size
        ];
        
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'create', [$request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid download URL', $result['data']['error']);
    }
    
    public function testUpdateProduct(): void
    {
        // Setup test data
        $requestData = [
            'name' => 'Updated Product',
            'price' => 29.99
        ];
        
        $product = $this->createMock(Product::class);
        $product->method('getType')->willReturn('physical');
        
        $updatedProduct = $this->createMock(Product::class);
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(123)
            ->willReturn($product);
            
        $this->productService->expects($this->once())
            ->method('updateProduct')
            ->with(123, $requestData)
            ->willReturn($updatedProduct);
            
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'update', [123, $request]);
        
        // Assertions
        $this->assertEquals(200, $result['status']);
        $this->assertSame($updatedProduct, $result['data']);
    }
    
    public function testUpdatePhysicalProductWithInvalidWeight(): void
    {
        // Setup test data with invalid weight
        $requestData = [
            'weight' => -5.0
        ];
        
        $product = $this->createMock(Product::class);
        $product->method('getType')->willReturn('physical');
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(123)
            ->willReturn($product);
            
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'update', [123, $request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid weight', $result['data']['error']);
    }
    
    public function testUpdateDigitalProductWithInvalidUrl(): void
    {
        // Setup test data with invalid URL
        $requestData = [
            'download_url' => 'not-a-valid-url'
        ];
        
        $product = $this->createMock(Product::class);
        $product->method('getType')->willReturn('digital');
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(123)
            ->willReturn($product);
            
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'update', [123, $request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid download URL', $result['data']['error']);
    }
    
    public function testUpdateDigitalProductWithInvalidFileSize(): void
    {
        // Setup test data with invalid file size
        $requestData = [
            'file_size' => -100
        ];
        
        $product = $this->createMock(Product::class);
        $product->method('getType')->willReturn('digital');
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(123)
            ->willReturn($product);
            
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'update', [123, $request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid file size', $result['data']['error']);
    }
    
    public function testUpdateProductWithException(): void
    {
        // Setup test data
        $requestData = [
            'name' => 'Updated Product'
        ];
        
        $product = $this->createMock(Product::class);
        $product->method('getType')->willReturn('physical');
        
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(123)
            ->willReturn($product);
            
        $this->productService->expects($this->once())
            ->method('updateProduct')
            ->willThrowException(new \Exception('Update failed'));
            
        // Create request with JSON data
        $request = $this->createRequestWithJson($requestData);
        
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'update', [123, $request]);
        
        // Assertions
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Update failed', $result['data']['error']);
    }
    
    public function testDeleteProduct(): void
    {
        // Configure mocks
        $this->productService->expects($this->once())
            ->method('deleteProduct')
            ->with(123);
            
        // Call method directly
        $result = $this->callProtectedJsonMethod($this->controller, 'delete', [123]);
        
        // Assertions
        $this->assertEquals(204, $result['status']);
        $this->assertNull($result['data']);
    }
    
    /**
     * Helper method to call json method through reflection and return the data and status
     */
    private function callProtectedJsonMethod($object, $methodName, array $parameters)
    {
        // Create reflection of the controller
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        // Create a subclass that captures json call results
        $testController = new class($this->productService, $this->serializer, $this->validator) extends ProductController {
            public $lastJsonCall = null;
            
            public function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                $this->lastJsonCall = [
                    'data' => $data,
                    'status' => $status,
                    'headers' => $headers,
                    'context' => $context
                ];
                return new JsonResponse($data, $status, $headers);
            }
        };
        
        // Call the method
        $method->invokeArgs($testController, $parameters);
        
        // Return the captured json call data
        return $testController->lastJsonCall;
    }
    
    /**
     * Helper to create a request with JSON content
     */
    private function createRequestWithJson(array $data): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn(json_encode($data));
        return $request;
    }
}
