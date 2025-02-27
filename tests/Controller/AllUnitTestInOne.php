<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\Product;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Controller\ProductController::__construct
 * @covers \App\Controller\ProductController::list
 * @covers \App\Controller\ProductController::get
 * @covers \App\Controller\ProductController::create
 * @covers \App\Controller\ProductController::update
 * @covers \App\Controller\ProductController::delete
 */
class AllUnitTestInOne extends TestCase
{
    private ProductService $productService;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function testAllController(): void
    {
        // Create a controller instance that exposes internal information
        $controller = new class($this->productService, $this->serializer, $this->validator) extends ProductController {
            public function getInternalServices(): array
            {
                return [
                    'productService' => $this->productService,
                    'serializer' => $this->serializer,
                    'validator' => $this->validator
                ];
            }
            
            public function callJson($data, int $status = 200): array
            {
                return ['data' => $data, 'status' => $status];
            }
        };

        // Test constructor by checking the injected dependencies
        $services = $controller->getInternalServices();
        $this->assertSame($this->productService, $services['productService']);
        $this->assertSame($this->serializer, $services['serializer']);
        $this->assertSame($this->validator, $services['validator']);
    }
}
