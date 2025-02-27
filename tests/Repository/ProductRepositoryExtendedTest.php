<?php

namespace App\Tests\Repository;

use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ProductRepositoryExtendedTest extends TestCase
{
    private ProductRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        
        // We need to allow any calls to these methods rather than expect them
        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
        
        $this->entityManager->method('getClassMetadata')
            ->willReturn(new ClassMetadata(Product::class));
        
        $this->repository = new ProductRepository($this->registry);
    }
    
    public function testCreateQueryBuilder(): void
    {
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
        
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(Product::class, 'p')
            ->willReturnSelf();
        
        $result = $this->repository->createQueryBuilder('p');
        
        $this->assertSame($this->queryBuilder, $result);
    }
    
    public function testCreatePaginator(): void
    {
        // Get the createPaginator method using reflection
        $reflectionClass = new \ReflectionClass(ProductRepository::class);
        $method = $reflectionClass->getMethod('createPaginator');
        $method->setAccessible(true);
        
        // Create a stub query that will work with Paginator constructor
        $query = $this->createStub(\Doctrine\ORM\Query::class);
        
        // Call the method directly
        $paginator = $method->invoke($this->repository, $query);
        
        // Assert the result is a Paginator instance
        $this->assertInstanceOf(\Doctrine\ORM\Tools\Pagination\Paginator::class, $paginator);
    }
    
    public function testCreatePaginatorWithCustomFactory(): void
    {
        // Get the createPaginator method using reflection
        $reflectionClass = new \ReflectionClass(ProductRepository::class);
        $method = $reflectionClass->getMethod('createPaginator');
        $method->setAccessible(true);
        
        // Create a stub query that works with Paginator
        $query = $this->createStub(\Doctrine\ORM\Query::class);
        
        // Create a mock paginator
        $mockPaginator = $this->createMock(\Doctrine\ORM\Tools\Pagination\Paginator::class);
        
        // Set a custom factory
        $this->repository->setPaginatorFactory(function($q) use ($mockPaginator) {
            return $mockPaginator;
        });
        
        // Call the method directly
        $paginator = $method->invoke($this->repository, $query);
        
        // Assert the result is our mock paginator
        $this->assertSame($mockPaginator, $paginator);
    }
}
