<?php

declare(strict_types=1);

namespace App\Kernel\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

final readonly class ConsoleApplicationFactory
{
    /**
     * @param iterable<Command> $commands
     */
    public function __construct(
        private iterable $commands,
    ) {}

    public function create(): Application
    {
        $application = new Application('StatusStorageFinal');

        foreach ($this->commands as $command) {
            $application->add($command);
        }

        return $application;
    }
}
