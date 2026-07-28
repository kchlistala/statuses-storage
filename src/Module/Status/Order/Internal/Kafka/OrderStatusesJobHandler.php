<?php

declare(strict_types=1);

namespace App\Module\Status\Order\Internal\Kafka;

use App\Module\Status\Order\Public\Event\OrderStatusesEvent;
use App\Module\Status\Order\Public\Event\StatusEntry;
use App\Shared\Avro\Public\AvroDecoderInterface;
use App\Shared\Kafka\Public\JobHandlerInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * Dummy consumer for the "OrderStatuses" Kafka event: decodes the Avro payload into
 * {@see OrderStatusesEvent} and does nothing further with it yet.
 */
final readonly class OrderStatusesJobHandler implements JobHandlerInterface
{
    private const string TASK_NAME = 'OrderStatuses';

    public function __construct(
        private AvroDecoderInterface $avroDecoder,
    ) {}

    #[\Override]
    public function supports(string $taskName): bool
    {
        return self::TASK_NAME === $taskName;
    }

    #[\Override]
    public function handle(ReceivedTaskInterface $task): void
    {
        $schemaJson = file_get_contents(__DIR__.'/order_statuses.avsc');

        if (false === $schemaJson) {
            throw new \RuntimeException('Unable to read order_statuses.avsc schema file.');
        }

        $datum = $this->avroDecoder->decode($task->getPayload(), $schemaJson);

        $rawStatuses = $datum['statuses'];

        if (!\is_array($rawStatuses)) {
            throw new \RuntimeException('Decoded "statuses" field is not an array.');
        }

        $statuses = array_map(
            /**
             * @param array<string, mixed> $status
             */
            static fn (array $status): StatusEntry => new StatusEntry(
                type: self::requireString($status['type']),
                value: self::requireString($status['value']),
            ),
            $rawStatuses,
        );

        new OrderStatusesEvent(
            orderId: $this->requireInt($datum['orderId']),
            statuses: $statuses,
            occuredAt: new \DateTimeImmutable(self::requireString($datum['occuredAt'])),
        );
    }

    private static function requireString(mixed $value): string
    {
        if (!\is_string($value)) {
            throw new \RuntimeException('Expected decoded Avro field to be a string.');
        }

        return $value;
    }

    private function requireInt(mixed $value): int
    {
        if (!\is_int($value)) {
            throw new \RuntimeException('Expected decoded Avro field to be an int.');
        }

        return $value;
    }
}
