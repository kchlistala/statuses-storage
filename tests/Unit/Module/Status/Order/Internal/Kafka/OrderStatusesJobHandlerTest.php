<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Status\Order\Internal\Kafka;

use App\Module\Status\Order\Internal\Kafka\OrderStatusesJobHandler;
use App\Shared\Avro\Internal\AvroSchemaDecoder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * @internal
 */
#[CoversNothing]
final class OrderStatusesJobHandlerTest extends TestCase
{
    public function testSupportsOnlyTheOrderStatusesTaskName(): void
    {
        $handler = new OrderStatusesJobHandler(new AvroSchemaDecoder());

        self::assertTrue($handler->supports('OrderStatuses'));
        self::assertFalse($handler->supports('SomethingElse'));
    }

    public function testHandleDecodesAValidAvroPayloadWithoutThrowing(): void
    {
        $handler = new OrderStatusesJobHandler(new AvroSchemaDecoder());

        $payload = $this->encode([
            'orderId' => 42,
            'statuses' => [
                ['type' => 'PAYMENT', 'value' => 'PAID'],
                ['type' => 'SHIPMENT', 'value' => 'DISPATCHED'],
            ],
            'occuredAt' => '2026-07-28T10:15:00+02:00',
        ]);

        $task = $this->createMock(ReceivedTaskInterface::class);
        $task->method('getName')->willReturn('OrderStatuses');
        $task->method('getPayload')->willReturn($payload);

        $handler->handle($task);

        $this->addToAssertionCount(1);
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
