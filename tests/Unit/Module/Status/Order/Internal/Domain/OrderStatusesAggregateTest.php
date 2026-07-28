<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Status\Order\Internal\Domain;

use App\Module\Status\Order\Internal\Domain\OrderStatusesAggregate;
use App\Module\Status\Order\Public\Event\StatusEntry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class OrderStatusesAggregateTest extends TestCase
{
    public function testCreateNewBuildsStatusesByTypeFromEntries(): void
    {
        $aggregate = OrderStatusesAggregate::createNew(42, [
            new StatusEntry('PAYMENT', 'PAID'),
            new StatusEntry('SHIPMENT', 'DISPATCHED'),
        ]);

        self::assertSame(42, $aggregate->getOrderId());
        self::assertSame(
            ['PAYMENT' => 'PAID', 'SHIPMENT' => 'DISPATCHED'],
            $aggregate->getStatusesByType(),
        );
    }

    public function testMergeStatusesOverwritesExistingTypeAndAddsNewType(): void
    {
        $aggregate = OrderStatusesAggregate::createNew(42, [
            new StatusEntry('PAYMENT', 'PENDING'),
            new StatusEntry('SHIPMENT', 'DISPATCHED'),
        ]);

        $aggregate->mergeStatuses([
            new StatusEntry('PAYMENT', 'PAID'),
            new StatusEntry('DELIVERY', 'DELIVERED'),
        ]);

        self::assertSame(
            ['PAYMENT' => 'PAID', 'SHIPMENT' => 'DISPATCHED', 'DELIVERY' => 'DELIVERED'],
            $aggregate->getStatusesByType(),
        );
    }

    public function testMergeStatusesBehavesTheSameWhenStartingFromHydratedState(): void
    {
        $aggregate = OrderStatusesAggregate::fromStatusesByType(42, [
            'PAYMENT' => 'PENDING',
            'SHIPMENT' => 'DISPATCHED',
        ]);

        $aggregate->mergeStatuses([
            new StatusEntry('PAYMENT', 'PAID'),
            new StatusEntry('DELIVERY', 'DELIVERED'),
        ]);

        self::assertSame(
            ['PAYMENT' => 'PAID', 'SHIPMENT' => 'DISPATCHED', 'DELIVERY' => 'DELIVERED'],
            $aggregate->getStatusesByType(),
        );
    }
}
