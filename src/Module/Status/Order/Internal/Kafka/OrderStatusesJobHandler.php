<?php

declare(strict_types=1);

namespace App\Module\Status\Order\Internal\Kafka;

use App\Module\Status\Order\Internal\Domain\OrderStatusesAggregate;
use App\Module\Status\Order\Internal\Repository\OrderStatusesRepository;
use App\Module\Status\Order\Public\Event\OrderStatusesEvent;
use App\Module\Status\Order\Public\Event\StatusEntry;
use App\Shared\Avro\Public\AvroDecoderInterface;
use App\Shared\Cache\Public\CacheInterface;
use App\Shared\Kafka\Public\JobHandlerInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * Consumer for the "OrderStatuses" Kafka event: decodes the Avro payload into an
 * {@see OrderStatusesEvent}, then merges its statuses into the in-memory cache
 * aggregate for that order (falling back to Postgres, then to a brand-new aggregate,
 * if nothing is cached yet). Writes only to cache — persisting to Postgres happens
 * elsewhere.
 */
final readonly class OrderStatusesJobHandler implements JobHandlerInterface
{
    private const string TASK_NAME = 'OrderStatuses';
    private const string CACHE_KEY_PREFIX = 'order-statuses:';

    public function __construct(
        private AvroDecoderInterface $avroDecoder,
        private CacheInterface $cache,
        private OrderStatusesRepository $repository,
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

        $event = new OrderStatusesEvent(
            orderId: $this->requireInt($datum['orderId']),
            statuses: $statuses,
            occuredAt: new \DateTimeImmutable(self::requireString($datum['occuredAt'])),
        );

        $cacheKey = self::CACHE_KEY_PREFIX.$event->orderId;

        $aggregate = $this->cache->get($cacheKey);

        if ($aggregate instanceof OrderStatusesAggregate) {
            $aggregate->mergeStatuses($event->statuses);
        } else {
            $aggregate = $this->repository->find($event->orderId);

            if ($aggregate instanceof OrderStatusesAggregate) {
                $aggregate->mergeStatuses($event->statuses);
            } else {
                $aggregate = OrderStatusesAggregate::createNew($event->orderId, $event->statuses);
            }
        }

        $this->cache->set($cacheKey, $aggregate);
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
