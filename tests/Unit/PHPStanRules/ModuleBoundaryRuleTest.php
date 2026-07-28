<?php

declare(strict_types=1);

namespace App\Tests\Unit\PHPStanRules;

use App\Tools\PHPStanRules\ModuleBoundaryRule;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Runs PHPStan (with only ModuleBoundaryRule enabled) against fixture modules to make sure
 * the rule actually flags cross-module Internal usage and leaves Public usage alone.
 *
 * @see ModuleBoundaryRule
 *
 * @internal
 */
#[CoversNothing]
final class ModuleBoundaryRuleTest extends TestCase
{
    public function testInternalNamespaceIsIsolatedPerModule(): void
    {
        $projectDir = \dirname(__DIR__, 3);

        $command = \sprintf(
            '%s analyse --configuration=%s --no-progress --error-format=json 2>&1',
            escapeshellarg($projectDir.'/vendor/bin/phpstan'),
            escapeshellarg($projectDir.'/tests/PHPStan/fixtures.neon'),
        );

        $output = shell_exec($command);
        self::assertIsString($output, 'Expected PHPStan to produce output.');

        $decoded = json_decode($output, true);
        self::assertIsArray($decoded, 'Expected valid JSON output from PHPStan, got: '.$output);

        $files = $decoded['files'] ?? [];
        self::assertIsArray($files);

        $validFile = $projectDir.'/tests/PHPStan/Fixtures/ModuleB/Internal/ValidUsage.php';
        $invalidFile = $projectDir.'/tests/PHPStan/Fixtures/ModuleB/Internal/InvalidUsage.php';

        self::assertArrayHasKey($invalidFile, $files, 'Cross-module Internal usage must be flagged.');

        self::assertContains(
            'app.internalBoundary',
            $this->messageIdentifiers($files, $invalidFile),
            'Expected ModuleBoundaryRule to report app.internalBoundary for InvalidUsage.php.',
        );

        self::assertNotContains(
            'app.internalBoundary',
            $this->messageIdentifiers($files, $validFile),
            'Valid cross-module Public usage must not be flagged by ModuleBoundaryRule.',
        );
    }

    /**
     * @param array<string, mixed> $files
     *
     * @return list<mixed>
     */
    private function messageIdentifiers(array $files, string $file): array
    {
        $entry = $files[$file] ?? [];
        self::assertIsArray($entry);

        $messages = $entry['messages'] ?? [];
        self::assertIsArray($messages);

        $identifiers = [];
        foreach ($messages as $message) {
            self::assertIsArray($message);
            $identifiers[] = $message['identifier'] ?? null;
        }

        return $identifiers;
    }
}
