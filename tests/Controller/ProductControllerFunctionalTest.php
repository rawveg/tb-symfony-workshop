<?php

namespace App\Tests\Controller;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Controller\ProductController;

class ProductControllerFunctionalTest extends TestCase
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
    
    /**
     * @dataProvider requestProvider
     */
    public function testList(Request $request, array $expectedServiceCall, string $expectedMethod): void
    {
        // Arrange
        $paginatedResult = [
            'items' => [$this->createMock(PhysicalProduct::class)],
            'total' => 10,
            'page' => 1,
            'limit' => 10
        ];
        
        $products = [$this->createMock(PhysicalProduct::class)];
        
        // Set up the service mock expectations based on expected method
        if ($expectedMethod === 'getPaginatedProducts') {
            $this->productService->expects($this->once())
                ->method('getPaginatedProducts')
                ->with(...$expectedServiceCall)
                ->willReturn($paginatedResult);
            
            $this->productService->expects($this->never())
                ->method('getAllProducts');
        } else {
            $this->productService->expects($this->never())
                ->method('getPaginatedProducts');
                
            $this->productService->expects($this->once())
                ->method('getAllProducts')
                ->willReturn($products);
        }
        
        // Override the json method to verify it's called correctly
        $controller = new class($this->productService, $this->serializer, $this->validator) extends ProductController {
            public function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                return new JsonResponse(['data' => $data, 'status' => $status, 'context' => $context]);
            }
        };
        
        // Act
        $response = $controller->list($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $jsonData = json_decode($response->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $jsonData['status']);
    }
    
    public function requestProvider(): array
    {
        return [
            'no_pagination' => [
                new Request(), 
                [], 
                'getAllProducts'
            ],
            'with_pagination' => [
                new Request(['page' => '2', 'limit' => '5']), 
                [2, 5], 
                'getPaginatedProducts'
            ],
            'with_pagination_default_limit' => [
                new Request(['page' => '2']), 
                [2, 10], 
                'getPaginatedProducts'
            ]
        ];
    }
}
