<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Final test to ensure the controller instance has the expected methods and properties
 * @covers \App\Controller\ProductController::__construct
 * @covers \App\Controller\ProductController::list
 * @covers \App\Controller\ProductController::get
 * @covers \App\Controller\ProductController::create
 * @covers \App\Controller\ProductController::update
 * @covers \App\Controller\ProductController::delete
 */
class ProductControllerFinalTest extends TestCase
{
    public function testControllerInstanceAndMethods(): void
    {
        // Create dependencies
        $productService = $this->createMock(ProductService::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        
        // Create instance
        $controller = new ProductController($productService, $serializer, $validator);
        
        // Assert it's the correct instance
        $this->assertInstanceOf(ProductController::class, $controller);
        
        // Check that the controller has the necessary methods
        $this->assertTrue(method_exists($controller, 'list'));
        $this->assertTrue(method_exists($controller, 'get'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
        
        // Verify method signatures accept the expected parameters
        $reflectionClass = new \ReflectionClass(ProductController::class);
        
        $listMethod = $reflectionClass->getMethod('list');
        $this->assertEquals(1, $listMethod->getNumberOfParameters());
        $this->assertEquals('request', $listMethod->getParameters()[0]->getName());
        
        $getMethod = $reflectionClass->getMethod('get');
        $this->assertEquals(1, $getMethod->getNumberOfParameters());
        $this->assertEquals('id', $getMethod->getParameters()[0]->getName());
        
        $createMethod = $reflectionClass->getMethod('create');
        $this->assertEquals(1, $createMethod->getNumberOfParameters());
        $this->assertEquals('request', $createMethod->getParameters()[0]->getName());
        
        $updateMethod = $reflectionClass->getMethod('update');
        $this->assertEquals(2, $updateMethod->getNumberOfParameters());
        $this->assertEquals('id', $updateMethod->getParameters()[0]->getName());
        $this->assertEquals('request', $updateMethod->getParameters()[1]->getName());
        
        $deleteMethod = $reflectionClass->getMethod('delete');
        $this->assertEquals(1, $deleteMethod->getNumberOfParameters());
        $this->assertEquals('id', $deleteMethod->getParameters()[0]->getName());
    }
}
