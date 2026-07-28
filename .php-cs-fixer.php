<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/tools',
        __DIR__ . '/config',
        __DIR__ . '/migrations',
    ])
    ->name('*.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP84Migration' => true,
        '@PHP8x4Migration:risky' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'phpdoc_to_comment' => false,
        'final_internal_class' => false,
        // PHPStan 1.x's bundled parser cannot yet parse `new X()->method()` without the
        // wrapping parentheses, so keep the classic `(new X())->method()` form for now.
        'new_expression_parentheses' => false,
    ])
    ->setFinder($finder);
