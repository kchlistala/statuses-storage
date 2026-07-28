<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Internal;

use App\Shared\Kafka\Public\KafkaMessage;
use App\Shared\Kafka\Public\ProducerInterface;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\KafkaOptions;

/**
 * Produces messages to Kafka through the RoadRunner Jobs plugin (pipeline "app-events-produce"),
 * so the application does not need a separate Kafka client library.
 */
final readonly class RoadRunnerJobsProducer implements ProducerInterface
{
    private const string PIPELINE = 'app-events-produce';

    public function __construct(
        private Jobs $jobs,
    ) {}

    #[\Override]
    public function publish(KafkaMessage $message): void
    {
        $options = new KafkaOptions(topic: $message->topic);
        $queue = $this->jobs->connect(self::PIPELINE, $options);

        $task = $queue->create($message->taskName, $message->payload, $options);

        foreach ($message->headers as $name => $value) {
            if ('' === $value) {
                continue;
            }

            $task = $task->withHeader($name, $value);
        }

        $queue->dispatch($task);
    }
}
