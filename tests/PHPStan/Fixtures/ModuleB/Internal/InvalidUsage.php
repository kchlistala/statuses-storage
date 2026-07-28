<?php

declare(strict_types=1);

namespace App\Tests\PHPStan\Fixtures\ModuleB\Internal;

use App\Tests\PHPStan\Fixtures\ModuleA\Internal\ModuleAImpl;

/**
 * Forbidden: ModuleB must not depend on ModuleA's Internal namespace.
 * This fixture exists so ModuleBoundaryRuleTest can assert the rule catches it.
 */
final class InvalidUsage
{
    public function __construct(
        private readonly ModuleAImpl $dependency,
    ) {}
}
