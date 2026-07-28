<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Public;

/**
 * DTO describing a message to be produced to Kafka via the "app-events-produce" pipeline.
 */
final readonly class KafkaMessage
{
    /**
     * @param non-empty-string                $topic
     * @param non-empty-string                $taskName name used to route the message to a {@see JobHandlerInterface} on consume
     * @param array<non-empty-string, string> $headers
     */
    public function __construct(
        public string $topic,
        public string $taskName,
        public string $payload,
        public array $headers = [],
    ) {}
}
