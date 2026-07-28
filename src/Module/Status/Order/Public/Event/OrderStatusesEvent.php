<?php

declare(strict_types=1);

namespace App\Module\Status\Order\Public\Event;

final readonly class OrderStatusesEvent
{
    /**
     * @param array<StatusEntry> $statuses
     */
    public function __construct(
        public int $orderId,
        public array $statuses,
        public \DateTimeImmutable $occuredAt,
    ) {}
}
