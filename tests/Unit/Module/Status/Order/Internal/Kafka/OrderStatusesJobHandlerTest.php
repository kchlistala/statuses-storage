<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Status\Order\Internal\Kafka;

use App\Module\Status\Order\Internal\Domain\OrderStatusesAggregate;
use App\Module\Status\Order\Internal\Kafka\OrderStatusesJobHandler;
use App\Module\Status\Order\Internal\Repository\OrderStatusesRepository;
use App\Shared\Avro\Internal\AvroSchemaDecoder;
use App\Shared\Cache\Public\CacheInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * @internal
 */
#[CoversNothing]
final class OrderStatusesJobHandlerTest extends TestCase
{
    private const string CACHE_KEY = 'order-statuses:42';

    public function testSupportsOnlyTheOrderStatusesTaskName(): void
    {
        $handler = $this->createHandler(
            $this->createMock(CacheInterface::class),
            $this->createMock(OrderStatusesRepository::class),
        );

        self::assertTrue($handler->supports('OrderStatuses'));
        self::assertFalse($handler->supports('SomethingElse'));
    }

    public function testHandleMergesIntoCachedAggregateWithoutTouchingRepository(): void
    {
        $cached = OrderStatusesAggregate::fromStatusesByType(42, [
            'PAYMENT' => 'PENDING',
            'SHIPMENT' => 'DISPATCHED',
        ]);

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->with(self::CACHE_KEY)->willReturn($cached);

        $repository = $this->createMock(OrderStatusesRepository::class);
        $repository->expects(self::never())->method('find');

        $cache->expects(self::once())
            ->method('set')
            ->with(self::CACHE_KEY, self::callback(
                static fn (OrderStatusesAggregate $aggregate): bool => [
                    'PAYMENT' => 'PAID',
                    'SHIPMENT' => 'DISPATCHED',
                ] === $aggregate->getStatusesByType(),
            ))
        ;

        $handler = $this->createHandler($cache, $repository);
        $handler->handle($this->createTask());
    }

    public function testHandleFallsBackToRepositoryOnCacheMiss(): void
    {
        $stored = OrderStatusesAggregate::fromStatusesByType(42, [
            'PAYMENT' => 'PENDING',
        ]);

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->with(self::CACHE_KEY)->willReturn(null);

        $repository = $this->createMock(OrderStatusesRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn($stored);

        $cache->expects(self::once())
            ->method('set')
            ->with(self::CACHE_KEY, self::callback(
                static fn (OrderStatusesAggregate $aggregate): bool => [
                    'PAYMENT' => 'PAID',
                ] === $aggregate->getStatusesByType(),
            ))
        ;

        $handler = $this->createHandler($cache, $repository);
        $handler->handle($this->createTask());
    }

    public function testHandleCreatesNewAggregateWhenNeitherCacheNorRepositoryHaveIt(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->with(self::CACHE_KEY)->willReturn(null);

        $repository = $this->createMock(OrderStatusesRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn(null);

        $cache->expects(self::once())
            ->method('set')
            ->with(self::CACHE_KEY, self::callback(
                static fn (OrderStatusesAggregate $aggregate): bool => [
                    'PAYMENT' => 'PAID',
                ] === $aggregate->getStatusesByType(),
            ))
        ;

        $handler = $this->createHandler($cache, $repository);
        $handler->handle($this->createTask());
    }

    private function createHandler(CacheInterface $cache, OrderStatusesRepository $repository): OrderStatusesJobHandler
    {
        return new OrderStatusesJobHandler(new AvroSchemaDecoder(), $cache, $repository);
    }

    private function createTask(): ReceivedTaskInterface
    {
        $payload = $this->encode([
            'orderId' => 42,
            'statuses' => [
                ['type' => 'PAYMENT', 'value' => 'PAID'],
            ],
            'occuredAt' => '2026-07-28T10:15:00+02:00',
        ]);

        $task = $this->createMock(ReceivedTaskInterface::class);
        $task->method('getName')->willReturn('OrderStatuses');
        $task->method('getPayload')->willReturn($payload);

        return $task;
    }

    /**
     * @param array<string, mixed> $datum
     */
    private function encode(array $datum): string
    {
        $schemaPath = \dirname((string) (new \ReflectionClass(OrderStatusesJobHandler::class))->getFileName())
            .'/order_statuses.avsc';
        $schemaJson = file_get_contents($schemaPath);
        self::assertIsString($schemaJson);

        $schema = \AvroSchema::parse($schemaJson);
        $io = new \AvroStringIO();
        $encoder = new \AvroIOBinaryEncoder($io);
        $writer = new \AvroIODatumWriter($schema);

        $writer->write($datum, $encoder);

        return $io->string();
    }
}
