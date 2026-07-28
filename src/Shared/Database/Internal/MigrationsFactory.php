<?php

declare(strict_types=1);

namespace App\Shared\Database\Internal;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;

final class MigrationsFactory
{
    public static function create(Connection $connection, string $projectDir): DependencyFactory
    {
        $configuration = new ConfigurationArray([
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions',
            ],
            'migrations_paths' => [
                'App\Migrations' => $projectDir.'/migrations',
            ],
        ]);

        return DependencyFactory::fromConnection($configuration, new ExistingConnection($connection));
    }
}
