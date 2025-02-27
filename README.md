# Symfony Workshop - Product API

This project implements a RESTful API for managing products using Symfony, following the Class Table Inheritance pattern for the database schema.

## Docker Setup

This project uses Docker for local development. The setup includes:

- PHP 8.2 (with FPM)
- Nginx web server
- PostgreSQL 16 database
- PgAdmin web interface

### Getting Started

1. Copy the environment configuration file:

```bash
cp .env.dist .env
```

2. Build and start the Docker containers:

```bash
docker compose up -d
```

3. Install Symfony and dependencies:

```bash
docker compose exec app composer install
```

4. The database connection is already configured in `.env`:

```
DATABASE_URL="postgresql://app:!ChangeMe!@database:5432/app?serverVersion=16&charset=utf8"
```

4. Create the database schema directly from entity definitions:

```bash
docker compose exec app php bin/console doctrine:schema:create
```

(Note: The database itself is automatically created when the Docker containers start.)

5. Load test data for demonstration:

```bash
docker compose exec app php bin/console doctrine:fixtures:load
```

This will create 6 sample products (3 physical, 3 digital) for testing purposes.

## Accessing the Application

- Web application: http://localhost:8080
- PgAdmin: http://localhost:8081 (email: admin@example.com, password: admin)

## API Authentication

All API requests require authentication using an X-API-Key header. If no valid API key is provided, the API will return a 401 Unauthorized response.

**For workshop purposes, use this hardcoded API key:**
```
X-API-Key: workshop_secret_api_key
```

## Running Tests

```bash
docker compose exec app php bin/phpunit
```

## API Documentation

This section provides detailed information about the API endpoints, including examples of how to use them.

### List Products

**GET /products**

Returns a list of all products.

**Example Request:**
```bash
curl -X GET \
  http://localhost:8080/products \
  -H 'X-API-Key: workshop_secret_api_key'
```

**Example Response:**
```json
[
  {
    "id": 1,
    "name": "Ergonomic Keyboard",
    "description": "A comfortable keyboard designed for long typing sessions with mechanical switches.",
    "price": 149.99,
    "sku": "KB-ERG-001",
    "weight": 1.2
  },
  {
    "id": 2,
    "name": "Programming E-Book",
    "description": "Comprehensive guide to modern PHP development with Symfony framework.",
    "price": 19.99,
    "download_url": "https://example.com/downloads/php-ebook",
    "file_size": 8500000
  }
]
```

### List Products with Pagination

**GET /products?page=1&limit=10**

Returns a paginated list of products.

**Query Parameters:**
- `page`: The page number (starts from 1)
- `limit`: Number of items per page (default: 10, max: 50)

**Example Request:**
```bash
curl -X GET \
  'http://localhost:8080/products?page=1&limit=10' \
  -H 'X-API-Key: workshop_secret_api_key'
```

**Example Response:**
```json
{
  "items": [
    {
      "id": 1,
      "name": "Ergonomic Keyboard",
      "description": "A comfortable keyboard designed for long typing sessions with mechanical switches.",
      "price": 149.99,
      "sku": "KB-ERG-001",
      "weight": 1.2
    }
  ],
  "total": 6,
  "page": 1,
  "limit": 10,
  "pages": 1
}
```

### Get a Single Product

**GET /products/{id}**

Returns details of a specific product.

**Example Request:**
```bash
curl -X GET \
  http://localhost:8080/products/1 \
  -H 'X-API-Key: workshop_secret_api_key'
```

**Example Response:**
```json
{
  "id": 1,
  "name": "Ergonomic Keyboard",
  "description": "A comfortable keyboard designed for long typing sessions with mechanical switches.",
  "price": 149.99,
  "sku": "KB-ERG-001",
  "weight": 1.2
}
```

### Create a New Product

**POST /products**

Creates a new product.

**Required Fields:**
- `type`: The product type (physical or digital)
- `name`: The product name
- `description`: The product description
- `price`: The product price (must be positive)

**Optional Fields for Physical Products:**
- `sku`: The product SKU
- `weight`: The product weight (must be positive or zero)

**Optional Fields for Digital Products:**
- `download_url`: The product download URL (must be a valid URL)
- `file_size`: The product file size in bytes (must be positive or zero)

**Example Request for Physical Product:**
```bash
curl -X POST \
  http://localhost:8080/products \
  -H 'X-API-Key: workshop_secret_api_key' \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "physical",
    "name": "Wireless Headphones",
    "description": "Premium wireless headphones with noise cancellation",
    "price": 199.99,
    "sku": "HD-WL-004",
    "weight": 0.3
}'
```

**Example Request for Digital Product:**
```bash
curl -X POST \
  http://localhost:8080/products \
  -H 'X-API-Key: workshop_secret_api_key' \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "digital",
    "name": "UI Design Course",
    "description": "Complete course on UI/UX design principles",
    "price": 39.99,
    "download_url": "https://example.com/downloads/ui-course",
    "file_size": 1200000000
}'
```

**Example Response:**
```json
{
  "id": 7,
  "name": "Wireless Headphones",
  "description": "Premium wireless headphones with noise cancellation",
  "price": 199.99,
  "sku": "HD-WL-004",
  "weight": 0.3
}
```

### Update a Product

**PUT /products/{id}**

Updates an existing product.

**Example Request:**
```bash
curl -X PUT \
  http://localhost:8080/products/1 \
  -H 'X-API-Key: workshop_secret_api_key' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Premium Ergonomic Keyboard",
    "price": 179.99
}'
```

**Example Response:**
```json
{
  "id": 1,
  "name": "Premium Ergonomic Keyboard",
  "description": "A comfortable keyboard designed for long typing sessions with mechanical switches.",
  "price": 179.99,
  "sku": "KB-ERG-001",
  "weight": 1.2
}
```

### Delete a Product

**DELETE /products/{id}**

Deletes a product.

**Example Request:**
```bash
curl -X DELETE \
  http://localhost:8080/products/1 \
  -H 'X-API-Key: workshop_secret_api_key'
```

**Example Response:**
No content (HTTP 204)

## Error Responses

### Invalid API Key

```json
{
  "error": "Invalid API Key",
  "message": "Authentication failed. Please provide a valid API key."
}
```

### Missing Required Fields

```json
{
  "error": "Missing required fields",
  "fields": ["name", "price"]
}
```

### Invalid Field Values

```json
{
  "error": "Invalid price",
  "message": "Price must be a positive number."
}
```

### Product Not Found

```json
{
  "error": "Product with ID 999 not found"
}
```