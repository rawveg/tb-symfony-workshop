<?php

namespace App\Tests\Controller;

use App\Controller\DefaultController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $controller = new class() extends DefaultController {
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                // Mock the render method
                return new Response($view);
            }
        };
        
        $response = $controller->index();
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('index.html.twig', $response->getContent());
    }
}
