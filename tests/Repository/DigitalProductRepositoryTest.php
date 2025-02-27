<?php

namespace App\Tests\Repository;

use App\Entity\DigitalProduct;
use App\Repository\DigitalProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class DigitalProductRepositoryTest extends TestCase
{
    public function testConstruct(): void
    {
        // Mock the necessary dependencies
        $registry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        // We need to allow any calls since the parent constructor will call these methods
        $registry->method('getManagerForClass')
            ->willReturn($entityManager);
            
        $entityManager->method('getClassMetadata')
            ->willReturn(new ClassMetadata(DigitalProduct::class));
        
        // Create the repository
        $repository = new DigitalProductRepository($registry);
        
        // Assert the repository was created successfully
        $this->assertInstanceOf(DigitalProductRepository::class, $repository);
    }
}
