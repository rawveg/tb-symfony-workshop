<?php

namespace App\Service;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository
    ) {
    }

    /**
     * Get all products
     *
     * @return array<Product>
     */
    public function getAllProducts(): array
    {
        return $this->productRepository->findAll();
    }
    
    /**
     * Get products with pagination
     *
     * @param int $page Current page (starts from 1)
     * @param int $limit Number of items per page
     * @return array Array with 'items' (products for current page) and pagination metadata
     */
    public function getPaginatedProducts(int $page = 1, int $limit = 10): array
    {
        return $this->productRepository->findPaginated($page, $limit);
    }

    /**
     * Get a product by ID
     *
     * @param int $id
     * @return Product
     * @throws NotFoundHttpException
     */
    public function getProductById(int $id): Product
    {
        $product = $this->productRepository->find($id);
        
        if (!$product) {
            throw new NotFoundHttpException(sprintf('Product with ID %d not found', $id));
        }
        
        return $product;
    }

    /**
     * Create a new product based on provided data
     *
     * @param array $data
     * @return Product
     */
    public function createProduct(array $data): Product
    {
        $type = $data['type'] ?? null;
        
        $product = match ($type) {
            'physical' => $this->createPhysicalProduct($data),
            'digital' => $this->createDigitalProduct($data),
            default => throw new \InvalidArgumentException('Invalid product type'),
        };
        
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        
        return $product;
    }

    /**
     * Update an existing product
     *
     * @param int $id
     * @param array $data
     * @return Product
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->getProductById($id);
        
        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        
        if (isset($data['price'])) {
            $product->setPrice($data['price']);
        }
        
        if ($product instanceof PhysicalProduct) {
            if (isset($data['sku'])) {
                $product->setSku($data['sku']);
            }
            
            if (isset($data['weight'])) {
                $product->setWeight($data['weight']);
            }
        } elseif ($product instanceof DigitalProduct) {
            if (isset($data['download_url'])) {
                $product->setDownloadUrl($data['download_url']);
            }
            
            if (isset($data['file_size'])) {
                $product->setFileSize($data['file_size']);
            }
        }
        
        $this->entityManager->flush();
        
        return $product;
    }

    /**
     * Delete a product by ID
     *
     * @param int $id
     * @return void
     */
    public function deleteProduct(int $id): void
    {
        $product = $this->getProductById($id);
        
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
    
    /**
     * Create a physical product
     *
     * @param array $data
     * @return PhysicalProduct
     */
    private function createPhysicalProduct(array $data): PhysicalProduct
    {
        $product = new PhysicalProduct();
        $product->setName($data['name']);
        $product->setDescription($data['description']);
        $product->setPrice($data['price']);
        
        if (isset($data['sku'])) {
            $product->setSku($data['sku']);
        }
        
        if (isset($data['weight'])) {
            $product->setWeight($data['weight']);
        }
        
        return $product;
    }
    
    /**
     * Create a digital product
     *
     * @param array $data
     * @return DigitalProduct
     */
    private function createDigitalProduct(array $data): DigitalProduct
    {
        $product = new DigitalProduct();
        $product->setName($data['name']);
        $product->setDescription($data['description']);
        $product->setPrice($data['price']);
        
        if (isset($data['download_url'])) {
            $product->setDownloadUrl($data['download_url']);
        }
        
        if (isset($data['file_size'])) {
            $product->setFileSize($data['file_size']);
        }
        
        return $product;
    }
}
