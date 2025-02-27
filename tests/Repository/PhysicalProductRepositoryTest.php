<?php

namespace App\Tests\Repository;

use App\Entity\PhysicalProduct;
use App\Repository\PhysicalProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class PhysicalProductRepositoryTest extends TestCase
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
            ->willReturn(new ClassMetadata(PhysicalProduct::class));
        
        // Create the repository
        $repository = new PhysicalProductRepository($registry);
        
        // Assert the repository was created successfully
        $this->assertInstanceOf(PhysicalProductRepository::class, $repository);
    }
}
