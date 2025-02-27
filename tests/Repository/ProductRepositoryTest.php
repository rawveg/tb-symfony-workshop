<?php

namespace App\Tests\Repository;

use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ProductRepositoryTest extends TestCase
{
    private ProductRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        
        $this->registry->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->entityManager);
        
        $this->entityManager->method('getClassMetadata')
            ->with(Product::class)
            ->willReturn(new ClassMetadata(Product::class));
        
        $this->repository = new ProductRepository($this->registry);
    }
    
    public function testSave(): void
    {
        $product = $this->createMock(PhysicalProduct::class);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($product);
        
        $this->entityManager->expects($this->never())
            ->method('flush');
        
        $this->repository->save($product, false);
    }
    
    public function testSaveWithFlush(): void
    {
        $product = $this->createMock(PhysicalProduct::class);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($product);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->save($product, true);
    }
    
    public function testRemove(): void
    {
        $product = $this->createMock(PhysicalProduct::class);
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($product);
        
        $this->entityManager->expects($this->never())
            ->method('flush');
        
        $this->repository->remove($product, false);
    }
    
    public function testRemoveWithFlush(): void
    {
        $product = $this->createMock(PhysicalProduct::class);
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($product);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->remove($product, true);
    }
    
    public function testFindPaginated(): void
    {
        // Use a proper approach with mocked dependencies
        $repository = $this->repository;
        
        // Create a QueryBuilder mock
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        // Create a Query mock
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        // Mock the entity manager to return our query builder
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
            
        // Configure QueryBuilder to return proper values and chain methods
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(Product::class, 'p')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('p.id', 'DESC')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
            
        // Create a custom paginator that will be used by createPaginator
        $mockItems = [new PhysicalProduct(), new PhysicalProduct()];
        
        // Create a paginator property reflection to inject our own mock
        $reflectionClass = new \ReflectionClass(ProductRepository::class);
        $paginatorFactoryProp = $reflectionClass->getProperty('paginatorFactory');
        $paginatorFactoryProp->setAccessible(true);
        
        // Create a paginator query mock that can handle setFirstResult and setMaxResults
        $mockPaginatorQuery = new class($query) {
            private $query;
            
            public function __construct($query) {
                $this->query = $query;
            }
            
            public function setFirstResult($offset) {
                return $this;
            }
            
            public function setMaxResults($limit) {
                return $this;
            }
        };
        
        // Create a real paginator instance to satisfy the type hint
        $paginator = $this->getMockBuilder(\Doctrine\ORM\Tools\Pagination\Paginator::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        // Configure the mock behavior
        $paginator->method('getIterator')
            ->willReturn(new \ArrayIterator($mockItems));
            
        $paginator->method('getQuery')
            ->willReturn($mockPaginatorQuery);
            
        $paginator->method('count')
            ->willReturn(20);
        
        // Set our factory to return the mock paginator
        $paginatorFactoryProp->setValue($repository, function($q) use ($paginator) {
            return $paginator;
        });
        
        // Execute the method
        $result = $repository->findPaginated(2, 5);
        
        // Verify the result
        $this->assertSame($mockItems, $result['items']);
        $this->assertEquals(20, $result['total']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(4, $result['pages']); // 20/5=4
        
        // Reset the property so it doesn't affect other tests
        $paginatorFactoryProp->setValue($repository, null);
    }
    
    public function testCreatePaginator(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        
        // Create a reflection to access the private method
        $repository = $this->repository;
        $reflection = new \ReflectionClass($repository);
        $method = $reflection->getMethod('createPaginator');
        $method->setAccessible(true);
        
        // Call the method
        $paginator = $method->invoke($repository, $query);
        
        // Verify it's a Paginator instance
        $this->assertInstanceOf(\Doctrine\ORM\Tools\Pagination\Paginator::class, $paginator);
    }
    
    public function testSetPaginatorFactory(): void
    {
        $repository = $this->repository;
        $mockQuery = $this->createMock(AbstractQuery::class);
        $mockPaginator = $this->createMock(\Doctrine\ORM\Tools\Pagination\Paginator::class);
        
        // Set a custom factory
        $repository->setPaginatorFactory(function($query) use ($mockPaginator, $mockQuery) {
            $this->assertSame($mockQuery, $query);
            return $mockPaginator;
        });
        
        // Use reflection to call the private createPaginator method
        $reflection = new \ReflectionClass($repository);
        $method = $reflection->getMethod('createPaginator');
        $method->setAccessible(true);
        
        // The factory should return our mock
        $result = $method->invoke($repository, $mockQuery);
        $this->assertSame($mockPaginator, $result);
    }
}
