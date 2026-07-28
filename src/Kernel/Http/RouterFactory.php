<?php

declare(strict_types=1);

namespace App\Kernel\Http;

use League\Route\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Builds the HTTP router for the foundation (no domain modules registered yet).
 */
final class RouterFactory
{
    public static function create(): Router
    {
        $router = new Router();

        $router->get('/health', static function (ServerRequestInterface $request): ResponseInterface {
            $factory = new Psr17Factory();
            $response = $factory->createResponse(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write((string) json_encode(['status' => 'ok']));

            return $response;
        });

        return $router;
    }
}
