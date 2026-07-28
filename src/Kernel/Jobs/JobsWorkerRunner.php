<?php

declare(strict_types=1);

namespace App\Kernel\Jobs;

use App\Shared\Kafka\Public\JobDispatcherInterface;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

final readonly class JobsWorkerRunner
{
    public function __construct(
        private JobDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {}

    public function run(): void
    {
        $consumer = new Consumer();

        while (($task = $consumer->waitTask()) instanceof ReceivedTaskInterface) {
            try {
                $this->dispatcher->dispatch($task);
                $task->ack();
            } catch (\Throwable $e) {
                $this->logger->error('Kafka job handling failed', [
                    'task' => $task->getName(),
                    'exception' => $e,
                ]);
                $task->nack($e);
            }
        }
    }
}
