<?php

declare(strict_types=1);

namespace App\Kernel\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

final readonly class HttpWorkerRunner
{
    public function __construct(
        private RequestHandlerInterface $handler,
    ) {}

    public function run(): void
    {
        $factory = new Psr17Factory();
        $worker = new PSR7Worker(Worker::create(), $factory, $factory, $factory);

        while (true) {
            try {
                $request = $worker->waitRequest();
            } catch (\Throwable $e) {
                $worker->getWorker()->error((string) $e);

                continue;
            }

            if (!$request instanceof ServerRequestInterface) {
                break;
            }

            try {
                $response = $this->handler->handle($request);
            } catch (\Throwable) {
                $response = $factory->createResponse(500);
                $response->getBody()->write('Internal Server Error');
            }

            $worker->respond($response);
        }
    }
}
