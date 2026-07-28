<?php

declare(strict_types=1);

namespace App\Tests\PHPStan\Fixtures\ModuleB\Internal;

use App\Tests\PHPStan\Fixtures\ModuleA\Public\ModuleAInterface;

/**
 * Allowed: ModuleB depends only on ModuleA's Public namespace.
 */
final class ValidUsage
{
    public function __construct(
        private readonly ModuleAInterface $dependency,
    ) {}
}
