<?php

namespace App\Tests\Entity;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    public function testPhysicalProductGetterSetters(): void
    {
        $product = new PhysicalProduct();
        
        // Test base Product properties
        $product->setName('Test Product');
        $product->setDescription('Test Description');
        $product->setPrice(19.99);
        
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('Test Description', $product->getDescription());
        $this->assertEquals(19.99, $product->getPrice());
        
        // Test PhysicalProduct specific properties
        $product->setSku('TEST-SKU');
        $product->setWeight(1.5);
        
        $this->assertEquals('TEST-SKU', $product->getSku());
        $this->assertEquals(1.5, $product->getWeight());
        
        // Test getType method
        $this->assertEquals('physical', $product->getType());
        
        // Test null values
        $product->setSku(null);
        $product->setWeight(null);
        
        $this->assertNull($product->getSku());
        $this->assertNull($product->getWeight());
    }
    
    public function testDigitalProductGetterSetters(): void
    {
        $product = new DigitalProduct();
        
        // Test base Product properties
        $product->setName('Digital Product');
        $product->setDescription('Digital Description');
        $product->setPrice(9.99);
        
        $this->assertEquals('Digital Product', $product->getName());
        $this->assertEquals('Digital Description', $product->getDescription());
        $this->assertEquals(9.99, $product->getPrice());
        
        // Test DigitalProduct specific properties
        $product->setDownloadUrl('https://example.com/download');
        $product->setFileSize(1024);
        
        $this->assertEquals('https://example.com/download', $product->getDownloadUrl());
        $this->assertEquals(1024, $product->getFileSize());
        
        // Test getType method
        $this->assertEquals('digital', $product->getType());
        
        // Test null values
        $product->setDownloadUrl(null);
        $product->setFileSize(null);
        
        $this->assertNull($product->getDownloadUrl());
        $this->assertNull($product->getFileSize());
    }
    
    public function testProductGetterSetters(): void
    {
        // Create a concrete implementation of the abstract Product class
        $product = new class extends Product {
            public function getType(): string {
                return 'test';
            }
        };
        
        // Test ID getter (default is null)
        $this->assertNull($product->getId());
        
        // Test Name getter/setter
        $product->setName('Test Name');
        $this->assertEquals('Test Name', $product->getName());
        
        // Test Description getter/setter
        $product->setDescription('Test Description');
        $this->assertEquals('Test Description', $product->getDescription());
        
        // Test Price getter/setter
        $product->setPrice(29.99);
        $this->assertEquals(29.99, $product->getPrice());
        
        // Test getType from our implementation
        $this->assertEquals('test', $product->getType());
    }
}
