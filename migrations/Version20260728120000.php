<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_statuses table (per-order aggregate of latest status value per type).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('order_statuses');

        $table->addColumn('order_id', Types::INTEGER, ['notnull' => true]);
        $table->addColumn('statuses', Types::JSONB, ['notnull' => true]);
        $table->addColumn('updated_at', Types::DATETIMETZ_IMMUTABLE, ['notnull' => true]);

        $table->setPrimaryKey(['order_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('order_statuses');
    }
}
