<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductControllerComprehensiveTest extends TestCase
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
        
        // Create a testable controller that overrides json method
        $this->controller = new class($this->productService, $this->serializer, $this->validator) extends ProductController {
            public function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                return new JsonResponse($data, $status, $headers);
            }
        };
    }
    
    public function testCreatePhysicalProduct(): void
    {
        // Mock the productService::createProduct to return a PhysicalProduct
        $mockProduct = $this->createMock(PhysicalProduct::class);
        $mockProduct->method('getId')->willReturn(1);
        $mockProduct->method('getName')->willReturn('Test Physical Product');
        $mockProduct->method('getDescription')->willReturn('Test Description');
        $mockProduct->method('getPrice')->willReturn(19.99);
        $mockProduct->method('getSku')->willReturn('TEST-123');
        $mockProduct->method('getWeight')->willReturn(1.5);
        
        $this->productService->expects($this->once())
            ->method('createProduct')
            ->willReturn($mockProduct);
            
        // Create a request with valid physical product data
        $requestData = [
            'type' => 'physical',
            'name' => 'Test Physical Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'sku' => 'TEST-123',
            'weight' => 1.5
        ];
        
        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        
        // Call the controller method
        $response = $this->controller->create($request);
        
        // Assert the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }
    
    public function testCreateWithServiceException(): void
    {
        // Setup the service to throw an exception
        $this->productService->expects($this->once())
            ->method('createProduct')
            ->willThrowException(new \InvalidArgumentException('Test exception'));
        
        // Create a request with valid product data
        $requestData = [
            'type' => 'physical',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99
        ];
        
        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        
        // Call the controller method
        $response = $this->controller->create($request);
        
        // Assert the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Test exception', $response->getContent());
    }
    
    public function testGetNonExistingProduct(): void
    {
        // Setup the service to throw a NotFoundHttpException
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with(999)
            ->willThrowException(new NotFoundHttpException('Product with ID 999 not found'));
        
        // We need to create a version of the controller that doesn't convert the exception
        $controller = new ProductController(
            $this->productService,
            $this->serializer,
            $this->validator
        );
        
        // Assert that an exception is thrown
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Product with ID 999 not found');
        
        // Call the controller method directly
        $controller->get(999);
    }
    
    public function testDeleteProductSuccess(): void
    {
        // Setup the service to not throw any exceptions
        $this->productService->expects($this->once())
            ->method('deleteProduct')
            ->with(1);
        
        // Call the controller method
        $response = $this->controller->delete(1);
        
        // Assert the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
    
    public function testDeleteNonExistingProduct(): void
    {
        // Setup the service to throw a NotFoundHttpException
        $this->productService->expects($this->once())
            ->method('deleteProduct')
            ->with(999)
            ->willThrowException(new NotFoundHttpException('Product with ID 999 not found'));
        
        // We need to create a version of the controller that doesn't convert the exception
        $controller = new ProductController(
            $this->productService,
            $this->serializer,
            $this->validator
        );
        
        // Assert that an exception is thrown
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Product with ID 999 not found');
        
        // Call the controller method directly
        $controller->delete(999);
    }
}
