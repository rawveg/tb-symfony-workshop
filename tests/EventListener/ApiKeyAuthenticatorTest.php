<?php

namespace App\Tests\EventListener;

use App\EventListener\ApiKeyAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApiKeyAuthenticatorTest extends TestCase
{
    private const API_KEY = 'test_api_key';
    private ApiKeyAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new ApiKeyAuthenticator(self::API_KEY);
    }

    public function testValidApiKey(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $headers = $this->createMock(HeaderBag::class);
        $request->headers = $headers;
        
        $request->method('getPathInfo')
            ->willReturn('/products');
        
        $headers->method('get')
            ->with('X-API-Key')
            ->willReturn(self::API_KEY);
        
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);
        
        // Assert that setResponse is never called on the event
        $event->expects($this->never())
            ->method('setResponse');
        
        // Act
        ($this->authenticator)($event);
    }

    public function testInvalidApiKey(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $headers = $this->createMock(HeaderBag::class);
        $request->headers = $headers;
        
        $request->method('getPathInfo')
            ->willReturn('/products');
        
        $headers->method('get')
            ->with('X-API-Key')
            ->willReturn('invalid_key');
        
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);
        
        // Assert that setResponse is called with an unauthorized response
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                $content = json_decode($response->getContent(), true);
                return $response instanceof JsonResponse
                    && $response->getStatusCode() === Response::HTTP_UNAUTHORIZED
                    && isset($content['error'])
                    && $content['error'] === 'Invalid API Key';
            }));
        
        // Act
        ($this->authenticator)($event);
    }

    public function testMissingApiKey(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $headers = $this->createMock(HeaderBag::class);
        $request->headers = $headers;
        
        $request->method('getPathInfo')
            ->willReturn('/products');
        
        $headers->method('get')
            ->with('X-API-Key')
            ->willReturn(null);
        
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);
        
        // Assert that setResponse is called with an unauthorized response
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                $content = json_decode($response->getContent(), true);
                return $response instanceof JsonResponse
                    && $response->getStatusCode() === Response::HTTP_UNAUTHORIZED
                    && isset($content['error'])
                    && $content['error'] === 'Invalid API Key';
            }));
        
        // Act
        ($this->authenticator)($event);
    }
    
    public function testNonApiRoute(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $headers = $this->createMock(HeaderBag::class);
        $request->headers = $headers;
        
        // A path that's not under the /products prefix
        $request->method('getPathInfo')
            ->willReturn('/some-other-route');
        
        // No API key provided
        $headers->method('get')
            ->with('X-API-Key')
            ->willReturn(null);
        
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);
        
        // Assert that setResponse is never called (authentication is skipped)
        $event->expects($this->never())
            ->method('setResponse');
        
        // Act
        ($this->authenticator)($event);
    }
    
    public function testApiKeyInjectionConstructor(): void
    {
        // Testing that constructor properly accepts the API key
        // This is a simple test but ensures dependency injection is working
        
        // Arrange & Act
        $customKey = 'custom_api_key_12345';
        $authenticator = new ApiKeyAuthenticator($customKey);
        
        // Create a reflectionClass to access private property
        $reflectionClass = new \ReflectionClass(ApiKeyAuthenticator::class);
        $apiKeyProperty = $reflectionClass->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        
        // Assert
        $this->assertEquals($customKey, $apiKeyProperty->getValue($authenticator));
    }
}
