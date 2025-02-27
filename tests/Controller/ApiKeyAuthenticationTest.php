<?php

namespace App\Tests\Controller;

use App\EventListener\ApiKeyAuthenticator;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApiKeyAuthenticationTest extends TestCase
{
    private const API_KEY = 'workshop_secret_api_key';
    private const INVALID_API_KEY = 'invalid_key';

    /**
     * @dataProvider apiEndpointsProvider
     */
    public function testApiEndpointRequiresValidApiKey(string $method, string $uri): void
    {
        // Create authenticator
        $authenticator = new ApiKeyAuthenticator(self::API_KEY);
        
        // Test with valid API key
        $requestWithValidKey = $this->createMockRequest($method, $uri, self::API_KEY);
        $eventWithValidKey = $this->createMockEvent($requestWithValidKey);
        
        $eventWithValidKey->expects($this->never())
            ->method('setResponse');
        
        ($authenticator)($eventWithValidKey);
        
        // Test with invalid API key
        $requestWithInvalidKey = $this->createMockRequest($method, $uri, self::INVALID_API_KEY);
        $eventWithInvalidKey = $this->createMockEvent($requestWithInvalidKey);
        
        $eventWithInvalidKey->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response->getStatusCode() === 401;
            }));
        
        ($authenticator)($eventWithInvalidKey);
        
        // Test with missing API key
        $requestWithNoKey = $this->createMockRequest($method, $uri, null);
        $eventWithNoKey = $this->createMockEvent($requestWithNoKey);
        
        $eventWithNoKey->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response->getStatusCode() === 401;
            }));
        
        ($authenticator)($eventWithNoKey);
    }
    
    public function apiEndpointsProvider(): array
    {
        return [
            'GET /products' => ['GET', '/products'],
            'POST /products' => ['POST', '/products'],
            'GET /products/1' => ['GET', '/products/1'],
            'PUT /products/1' => ['PUT', '/products/1'],
            'DELETE /products/1' => ['DELETE', '/products/1'],
        ];
    }
    
    public function testApiKeyResponseContainsErrorMessage(): void
    {
        $authenticator = new ApiKeyAuthenticator(self::API_KEY);
        $request = $this->createMockRequest('GET', '/products', null);
        $event = $this->createMockEvent($request);
        
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                $content = json_decode($response->getContent(), true);
                return $response instanceof JsonResponse
                    && isset($content['error'])
                    && $content['error'] === 'Invalid API Key'
                    && isset($content['message']);
            }));
        
        ($authenticator)($event);
    }
    
    public function testNonApiRoute(): void
    {
        $authenticator = new ApiKeyAuthenticator(self::API_KEY);
        $request = $this->createMockRequest('GET', '/some-other-route', null);
        $event = $this->createMockEvent($request);
        
        $event->expects($this->never())
            ->method('setResponse');
        
        ($authenticator)($event);
    }
    
    private function createMockRequest(string $method, string $uri, ?string $apiKey): Request
    {
        $request = $this->createMock(Request::class);
        $headers = $this->createMock(HeaderBag::class);
        $request->headers = $headers;
        
        $request->method('getPathInfo')
            ->willReturn($uri);
        
        $headers->method('get')
            ->with('X-API-Key')
            ->willReturn($apiKey);
        
        return $request;
    }
    
    private function createMockEvent(Request $request): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);
        
        return $event;
    }
}
