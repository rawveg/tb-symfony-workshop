<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\PhysicalProduct;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductControllerPathTest extends TestCase
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
            public function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                return new JsonResponse($data, $status, $headers);
            }
        };
    }
    
    /**
     * Test all possible paths in the list method by testing with different query parameters
     */
    public function testListWithNoParameters(): void
    {
        // Empty request - should return all products
        $request = new Request();
        
        $products = [
            $this->createMock(PhysicalProduct::class),
            $this->createMock(PhysicalProduct::class)
        ];
        
        $this->productService->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);
            
        $response = $this->controller->list($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
    
    public function testListWithPagination(): void
    {
        // Request with pagination parameters
        $request = new Request(['page' => '2', 'limit' => '5']);
        
        $paginatedResult = [
            'items' => [$this->createMock(PhysicalProduct::class)],
            'total' => 15,
            'page' => 2,
            'limit' => 5,
            'pages' => 3
        ];
        
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(2, 5)
            ->willReturn($paginatedResult);
            
        $response = $this->controller->list($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
    
    public function testListWithInvalidPagination(): void
    {
        // Request with invalid pagination parameters that should be corrected
        $request = new Request(['page' => '-1', 'limit' => '100']);
        
        $paginatedResult = [
            'items' => [$this->createMock(PhysicalProduct::class)],
            'total' => 15,
            'page' => 1,
            'limit' => 50,
            'pages' => 1
        ];
        
        // Should use corrected values: page=1 (min), limit=50 (max)
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(1, 50)
            ->willReturn($paginatedResult);
            
        $response = $this->controller->list($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
    
    public function testListWithPageNoLimit(): void
    {
        // Request with only page parameter
        $request = new Request(['page' => '3']);
        
        $paginatedResult = [
            'items' => [$this->createMock(PhysicalProduct::class)],
            'total' => 25,
            'page' => 3,
            'limit' => 10,
            'pages' => 3
        ];
        
        // Should use default limit=10
        $this->productService->expects($this->once())
            ->method('getPaginatedProducts')
            ->with(3, 10)
            ->willReturn($paginatedResult);
            
        $response = $this->controller->list($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
