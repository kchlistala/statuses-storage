<?php

declare(strict_types=1);

namespace App\Tests\Integration\Module\Status\Order\Internal\Repository;

use App\Module\Status\Order\Internal\Repository\OrderStatusesRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class OrderStatusesRepositoryTest extends TestCase
{
    private Connection $connection;
    private OrderStatusesRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        self::assertIsString($databaseUrl, 'DATABASE_URL must be set to run integration tests.');

        $parser = new DsnParser([
            'postgresql' => 'pdo_pgsql',
            'postgres' => 'pdo_pgsql',
        ]);

        $this->connection = DriverManager::getConnection($parser->parse($databaseUrl));
        $this->connection->beginTransaction();

        $this->repository = new OrderStatusesRepository($this->connection);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->connection->rollBack();
        $this->connection->close();
    }

    public function testFindReturnsNullForAMissingOrder(): void
    {
        self::assertNull($this->repository->find(999999));
    }

    public function testFindHydratesAnAggregateFromAnExistingRow(): void
    {
        $this->connection->insert('order_statuses', [
            'order_id' => 123,
            'statuses' => ['PAYMENT' => 'PAID', 'SHIPMENT' => 'DISPATCHED'],
            'updated_at' => new \DateTimeImmutable(),
        ], [
            'order_id' => Types::INTEGER,
            'statuses' => Types::JSONB,
            'updated_at' => Types::DATETIMETZ_IMMUTABLE,
        ]);

        $aggregate = $this->repository->find(123);

        self::assertNotNull($aggregate);
        self::assertSame(123, $aggregate->getOrderId());
        self::assertSame(
            ['PAYMENT' => 'PAID', 'SHIPMENT' => 'DISPATCHED'],
            $aggregate->getStatusesByType(),
        );
    }
}
