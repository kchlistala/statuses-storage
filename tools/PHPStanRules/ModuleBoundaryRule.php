<?php

declare(strict_types=1);

namespace App\Tools\PHPStanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that any `...\Internal\...` class is only referenced from within its own
 * owning namespace (the part of the FQCN before `\Internal\`).
 *
 * This gives full N x N isolation between modules (and submodules) without having to
 * enumerate every module pair in deptrac.yaml: for any class name matched by this rule,
 * e.g. `App\Module\Status\Order\Internal\OrderRepository`, only code whose own class name
 * starts with `App\Module\Status\Order\` is allowed to reference it. Code in
 * `App\Module\Status\OrderItem\...` or in any other module is rejected, and no per-module
 * configuration is needed as new modules are added.
 *
 * PHPUnit test classes under the `Unit`/`Integration` testsuite roots (see phpunit.xml.dist)
 * are evaluated against the src-equivalent namespace they mirror: a test at
 * `tests/Unit/Module/Status/Order/Internal/Kafka/FooTest.php`
 * (`App\Tests\Unit\Module\Status\Order\Internal\Kafka\FooTest`) is treated as if it were
 * `App\Module\Status\Order\Internal\Kafka\FooTest`, so it may reach into the Internal
 * namespace of the module it belongs to and tests - exactly like production code under that
 * namespace would - while still being rejected for any other module's Internal namespace.
 * This does not affect `tests/PHPStan/Fixtures/*`, which is a self-contained rule sandbox
 * excluded from the main analysis (see phpstan.neon) and analysed separately via
 * tests/PHPStan/fixtures.neon.
 *
 * @implements Rule<Node\Name\FullyQualified>
 */
final class ModuleBoundaryRule implements Rule
{
    private const array TEST_SUITE_PREFIXES = [
        'App\Tests\Unit\\',
        'App\Tests\Integration\\',
    ];

    #[\Override]
    public function getNodeType(): string
    {
        return Node\Name\FullyQualified::class;
    }

    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        \assert($node instanceof Node\Name\FullyQualified);

        $referencedClass = ltrim($node->toString(), '\\');

        $internalPos = strpos($referencedClass, '\Internal\\');
        if (false === $internalPos) {
            return [];
        }

        $owningPrefix = substr($referencedClass, 0, $internalPos);

        $callerClass = $scope->getClassReflection()?->getName();
        if (null === $callerClass) {
            return [];
        }

        $effectiveCallerClass = $this->resolveEffectiveClassName($callerClass);

        if ($effectiveCallerClass === $referencedClass || str_starts_with($effectiveCallerClass, $owningPrefix.'\\')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf(
                '%s must not reference %s: only code within "%s\*" may use its Internal namespace, '
                .'other modules must depend on its Public namespace instead.',
                $callerClass,
                $referencedClass,
                $owningPrefix,
            ))
                ->identifier('app.internalBoundary')
                ->build(),
        ];
    }

    private function resolveEffectiveClassName(string $className): string
    {
        foreach (self::TEST_SUITE_PREFIXES as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return 'App\\'.substr($className, \strlen($prefix));
            }
        }

        return $className;
    }
}
