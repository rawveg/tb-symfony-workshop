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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductControllerValidationTest extends TestCase
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
    
    public function testUpdateDigitalProductValidations(): void
    {
        // Setup a digital product for testing
        $productId = 1;
        $product = $this->createMock(DigitalProduct::class);
        $product->method('getType')->willReturn('digital');
        
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willReturn($product);
        
        // Invalid URL test
        $requestData = [
            'download_url' => 'not-a-valid-url',
            'file_size' => 1024
        ];
        
        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        
        $response = $this->controller->update($productId, $request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Invalid download URL', $response->getContent());
    }
    
    public function testUpdateDigitalProductWithInvalidFileSize(): void
    {
        // Setup a digital product for testing
        $productId = 1;
        $product = $this->createMock(DigitalProduct::class);
        $product->method('getType')->willReturn('digital');
        
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willReturn($product);
        
        // Invalid file size test
        $requestData = [
            'download_url' => 'https://example.com/download',
            'file_size' => -100 // negative file size
        ];
        
        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        
        $response = $this->controller->update($productId, $request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Invalid file size', $response->getContent());
    }
    
    public function testUpdatePhysicalProductValidations(): void
    {
        // Setup a physical product for testing
        $productId = 1;
        $product = $this->createMock(PhysicalProduct::class);
        $product->method('getType')->willReturn('physical');
        
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willReturn($product);
        
        // Invalid weight test
        $requestData = [
            'sku' => 'TEST-123',
            'weight' => -2.5 // negative weight
        ];
        
        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        
        $response = $this->controller->update($productId, $request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Invalid weight', $response->getContent());
    }
    
    public function testUpdateProductWithExceptionFromService(): void
    {
        // Setup a product ID
        $productId = 1;
        
        // Make the service throw an exception
        $this->productService->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willThrowException(new \Exception('Service error'));
        
        $requestData = [
            'name' => 'Test Product'
        ];
        
        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        
        $response = $this->controller->update($productId, $request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Service error', $response->getContent());
    }
}
