<?php

declare(strict_types=1);

namespace App\Shared\Database\Public;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Base class for module repositories built on Doctrine DBAL (no ORM).
 * Lives in Public so repositories in any module (and submodule) can extend it.
 */
abstract class AbstractRepository
{
    public function __construct(
        protected readonly Connection $connection,
    ) {}

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }
}
