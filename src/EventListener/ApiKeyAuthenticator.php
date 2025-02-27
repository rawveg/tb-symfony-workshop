<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthenticator
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Skip authentication for non-API routes (if needed)
        if (!$this->isApiRoute($request)) {
            return;
        }

        $apiKey = $request->headers->get('X-API-Key');
        
        if (null === $apiKey || $apiKey !== $this->apiKey) {
            $response = new JsonResponse([
                'error' => 'Invalid API Key',
                'message' => 'Authentication failed. Please provide a valid API key.'
            ], Response::HTTP_UNAUTHORIZED);
            
            $event->setResponse($response);
        }
    }

    private function isApiRoute(Request $request): bool
    {
        // Check if the route is an API route (all routes in this project are API routes)
        return str_starts_with($request->getPathInfo(), '/products');
    }
}
