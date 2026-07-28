<?php

declare(strict_types=1);

namespace App\Module\Status\Order\Public\Event;

final readonly class StatusEntry
{
    public function __construct(
        public string $type,
        public string $value,
    ) {}
}
