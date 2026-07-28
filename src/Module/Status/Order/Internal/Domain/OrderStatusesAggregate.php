<?php

declare(strict_types=1);

namespace App\Module\Status\Order\Internal\Domain;

use App\Module\Status\Order\Public\Event\StatusEntry;

/**
 * All known statuses for a single order, keyed by status type. Mutable: new status
 * entries for an existing type overwrite that type's value; new types are added.
 */
final class OrderStatusesAggregate
{
    /**
     * @param array<string, string> $statusesByType type => value
     */
    private function __construct(
        private readonly int $orderId,
        private array $statusesByType,
    ) {}

    /**
     * @param array<StatusEntry> $statuses
     */
    public static function createNew(int $orderId, array $statuses): self
    {
        $aggregate = new self($orderId, []);
        $aggregate->mergeStatuses($statuses);

        return $aggregate;
    }

    /**
     * @param array<string, string> $statusesByType
     */
    public static function fromStatusesByType(int $orderId, array $statusesByType): self
    {
        return new self($orderId, $statusesByType);
    }

    /**
     * @param array<StatusEntry> $newEntries
     */
    public function mergeStatuses(array $newEntries): void
    {
        foreach ($newEntries as $entry) {
            $this->statusesByType[$entry->type] = $entry->value;
        }
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return array<string, string>
     */
    public function getStatusesByType(): array
    {
        return $this->statusesByType;
    }
}
