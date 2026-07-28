<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Internal;

use App\Shared\Kafka\Public\JobDispatcherInterface;
use App\Shared\Kafka\Public\JobHandlerInterface;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * Routes a consumed Kafka task to the first registered {@see JobHandlerInterface} that supports it.
 */
final readonly class TaggedJobDispatcher implements JobDispatcherInterface
{
    /**
     * @param iterable<JobHandlerInterface> $handlers
     */
    public function __construct(
        private iterable $handlers,
        private LoggerInterface $logger,
    ) {}

    #[\Override]
    public function dispatch(ReceivedTaskInterface $task): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($task->getName())) {
                $handler->handle($task);

                return;
            }
        }

        $this->logger->warning('No Kafka job handler registered for task', ['task' => $task->getName()]);
    }
}
