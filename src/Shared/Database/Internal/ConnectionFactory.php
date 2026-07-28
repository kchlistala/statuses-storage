<?php

declare(strict_types=1);

namespace App\Shared\Database\Internal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

final class ConnectionFactory
{
    public static function create(string $databaseUrl): Connection
    {
        $parser = new DsnParser([
            'postgresql' => 'pdo_pgsql',
            'postgres' => 'pdo_pgsql',
        ]);

        return DriverManager::getConnection($parser->parse($databaseUrl));
    }
}
