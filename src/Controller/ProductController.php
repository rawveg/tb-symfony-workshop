<?php

namespace App\Controller;

use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API Controller for product management
 * 
 * This controller provides endpoints for managing products in the catalog.
 * All endpoints require authentication with a valid X-API-Key header.
 * There are two product types available: physical and digital products.
 */
#[Route('/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * List all products with optional pagination
     * 
     * This endpoint returns a list of all products in the catalog.
     * It supports pagination with the following query parameters:
     * - page: The page number (starts from 1)
     * - limit: Number of items per page (default: 10, max: 50)
     * 
     * When pagination is used, the response includes:
     * - items: Array of products for the current page
     * - total: Total number of products
     * - page: Current page number
     * - limit: Number of items per page
     * - pages: Total number of pages
     * 
     * @param Request $request The HTTP request
     * @return JsonResponse The JSON response with products
     */
    #[Route('', name: 'product_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Check if pagination is requested
        if ($request->query->has('page')) {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = max(1, min(50, $request->query->getInt('limit', 10)));
            
            $result = $this->productService->getPaginatedProducts($page, $limit);
            
            return $this->json($result, Response::HTTP_OK, [], [
                'groups' => ['product:read']
            ]);
        }
        
        // Return all products if no pagination requested
        $products = $this->productService->getAllProducts();
        
        // Ensure we have the proper context for serialization
        $context = [
            'groups' => ['product:read'],
            'enable_max_depth' => true,
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ];
        
        return $this->json($products, Response::HTTP_OK, [], $context);
    }

    /**
     * Get a single product by its ID
     * 
     * This endpoint returns detailed information about a specific product.
     * 
     * @param int $id The product ID
     * @return JsonResponse The JSON response with product details
     * @throws NotFoundHttpException If the product doesn't exist
     */
    #[Route('/{id}', name: 'product_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        
        // Ensure we have the proper context for serialization
        $context = [
            'groups' => ['product:read'],
            'enable_max_depth' => true,
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ];
        
        return $this->json($product, Response::HTTP_OK, [], $context);
    }

    /**
     * Create a new product
     * 
     * This endpoint creates a new product based on the provided JSON data.
     * Required fields:
     * - type: The product type (physical or digital)
     * - name: The product name
     * - description: The product description
     * - price: The product price (must be positive)
     * 
     * For physical products, the following fields are optional:
     * - sku: The product SKU
     * - weight: The product weight (must be positive or zero)
     * 
     * For digital products, the following fields are optional:
     * - download_url: The product download URL (must be a valid URL)
     * - file_size: The product file size in bytes (must be positive or zero)
     * 
     * @param Request $request The HTTP request with JSON payload
     * @return JsonResponse The JSON response with created product details
     */
    #[Route('', name: 'product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        
        // Validate required fields
        $requiredFields = ['type', 'name', 'description', 'price'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return $this->json([
                'error' => 'Missing required fields',
                'fields' => $missingFields
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Validate product type
        if (!in_array($data['type'], ['physical', 'digital'])) {
            return $this->json([
                'error' => 'Invalid product type',
                'message' => 'Product type must be either "physical" or "digital".'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Validate price is a positive number
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            return $this->json([
                'error' => 'Invalid price',
                'message' => 'Price must be a positive number.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Type-specific validations
        if ($data['type'] === 'physical') {
            // For physical products, validate weight if provided
            if (isset($data['weight']) && (!is_numeric($data['weight']) || $data['weight'] < 0)) {
                return $this->json([
                    'error' => 'Invalid weight',
                    'message' => 'Weight must be a positive number or zero.'
                ], Response::HTTP_BAD_REQUEST);
            }
        } else if ($data['type'] === 'digital') {
            // For digital products, validate download URL if provided
            if (isset($data['download_url']) && !filter_var($data['download_url'], FILTER_VALIDATE_URL)) {
                return $this->json([
                    'error' => 'Invalid download URL',
                    'message' => 'Download URL must be a valid URL.'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Validate file size if provided
            if (isset($data['file_size']) && (!is_numeric($data['file_size']) || $data['file_size'] < 0)) {
                return $this->json([
                    'error' => 'Invalid file size',
                    'message' => 'File size must be a positive integer or zero.'
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        
        try {
            $product = $this->productService->createProduct($data);
            
            // Ensure we have the proper context for serialization
            $context = [
                'groups' => ['product:read'],
                'enable_max_depth' => true,
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ];
            
            return $this->json($product, Response::HTTP_CREATED, [], $context);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update an existing product
     * 
     * This endpoint updates an existing product based on the provided JSON data.
     * Any fields not included in the request will not be modified.
     * 
     * Available fields:
     * - name: The product name
     * - description: The product description
     * - price: The product price (must be positive)
     * 
     * For physical products, the following fields can be updated:
     * - sku: The product SKU
     * - weight: The product weight (must be positive or zero)
     * 
     * For digital products, the following fields can be updated:
     * - download_url: The product download URL (must be a valid URL)
     * - file_size: The product file size in bytes (must be positive or zero)
     * 
     * @param int $id The product ID to update
     * @param Request $request The HTTP request with JSON payload
     * @return JsonResponse The JSON response with updated product details
     * @throws NotFoundHttpException If the product doesn't exist
     */
    #[Route('/{id}', name: 'product_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        
        // Validate price if provided
        if (isset($data['price'])) {
            if (!is_numeric($data['price']) || $data['price'] <= 0) {
                return $this->json([
                    'error' => 'Invalid price',
                    'message' => 'Price must be a positive number.'
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        
        // Get product to determine its type
        try {
            $product = $this->productService->getProductById($id);
            
            // Type-specific validations
            if ($product->getType() === 'physical') {
                // For physical products, validate weight if provided
                if (isset($data['weight']) && (!is_numeric($data['weight']) || $data['weight'] < 0)) {
                    return $this->json([
                        'error' => 'Invalid weight',
                        'message' => 'Weight must be a positive number or zero.'
                    ], Response::HTTP_BAD_REQUEST);
                }
            } else if ($product->getType() === 'digital') {
                // For digital products, validate download URL if provided
                if (isset($data['download_url']) && !filter_var($data['download_url'], FILTER_VALIDATE_URL)) {
                    return $this->json([
                        'error' => 'Invalid download URL',
                        'message' => 'Download URL must be a valid URL.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                // Validate file size if provided
                if (isset($data['file_size']) && (!is_numeric($data['file_size']) || $data['file_size'] < 0)) {
                    return $this->json([
                        'error' => 'Invalid file size',
                        'message' => 'File size must be a positive integer or zero.'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
            
            // Update product
            $product = $this->productService->updateProduct($id, $data);
            
            // Ensure we have the proper context for serialization
            $context = [
                'groups' => ['product:read'],
                'enable_max_depth' => true,
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ];
            
            return $this->json($product, Response::HTTP_OK, [], $context);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Delete a product
     * 
     * This endpoint deletes a product by its ID.
     * If successful, it returns a 204 No Content response.
     * 
     * @param int $id The product ID to delete
     * @return JsonResponse An empty response with 204 status code
     * @throws NotFoundHttpException If the product doesn't exist
     */
    #[Route('/{id}', name: 'product_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
