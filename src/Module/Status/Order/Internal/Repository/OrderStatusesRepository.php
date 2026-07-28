<?php

declare(strict_types=1);

namespace App\Module\Status\Order\Internal\Repository;

use App\Module\Status\Order\Internal\Domain\OrderStatusesAggregate;
use App\Shared\Database\Public\AbstractRepository;

class OrderStatusesRepository extends AbstractRepository
{
    public function find(int $orderId): ?OrderStatusesAggregate
    {
        $row = $this->createQueryBuilder()
            ->select('order_id', 'statuses')
            ->from('order_statuses')
            ->where('order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->fetchAssociative()
        ;

        if (false === $row) {
            return null;
        }

        $statuses = $row['statuses'];
        $orderId = $row['order_id'];

        if (!\is_string($statuses) || (!\is_int($orderId) && !\is_string($orderId))) {
            throw new \RuntimeException('Unexpected column type in "order_statuses" row.');
        }

        /** @var array<string, string> $statusesByType */
        $statusesByType = json_decode($statuses, true, 512, JSON_THROW_ON_ERROR);

        return OrderStatusesAggregate::fromStatusesByType((int) $orderId, $statusesByType);
    }
}
