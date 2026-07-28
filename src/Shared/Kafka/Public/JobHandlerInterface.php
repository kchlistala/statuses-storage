<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Public;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * Extension point for modules that need to react to messages consumed from Kafka.
 *
 * Register the implementation as a tagged service with `app.kafka_job_handler`
 * (done automatically via the `_instanceof` autoconfiguration in config/services.yaml).
 */
interface JobHandlerInterface
{
    public function supports(string $taskName): bool;

    public function handle(ReceivedTaskInterface $task): void;
}
