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
 * @implements Rule<Node\Name\FullyQualified>
 */
final class ModuleBoundaryRule implements Rule
{
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

        if ($callerClass === $referencedClass || str_starts_with($callerClass, $owningPrefix.'\\')) {
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
}
