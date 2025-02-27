<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    /** @var callable|null */
    private $paginatorFactory = null;
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    /**
     * Find products with pagination
     *
     * @param int $page Current page (starts from 1)
     * @param int $limit Number of items per page
     * @return array Array with 'items' (products for current page) and metadata about pagination
     */
    public function findPaginated(int $page = 1, int $limit = 10): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('p')
            ->from(Product::class, 'p')
            ->orderBy('p.id', 'DESC');
        
        $query = $queryBuilder->getQuery();
        $paginator = $this->createPaginator($query);
        
        $paginator
            ->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        
        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => count($paginator),
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil(count($paginator) / $limit)
        ];
    }
    
    /**
     * Factory function for creating the Paginator
     * This is primarily used for testing to inject a mock
     *
     * @param mixed $query
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    private function createPaginator($query): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        if (isset($this->paginatorFactory)) {
            return ($this->paginatorFactory)($query);
        }
        
        return new \Doctrine\ORM\Tools\Pagination\Paginator($query);
    }
    
    /**
     * Set a custom paginator factory function for testing
     *
     * @param callable $factory
     * @return void
     */
    public function setPaginatorFactory(callable $factory): void
    {
        $this->paginatorFactory = $factory;
    }
}
