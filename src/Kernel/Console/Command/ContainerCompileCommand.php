<?php

declare(strict_types=1);

namespace App\Kernel\Console\Command;

use App\Kernel\ContainerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:container:compile',
    description: 'Compiles and caches the DI container for the given environment',
)]
final class ContainerCompileCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('env', InputArgument::OPTIONAL, 'Environment to compile the container for', 'prod');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $env = $input->getArgument('env');
        $env = \is_string($env) ? $env : 'prod';

        (new ContainerFactory($this->projectDir, $env, false))->create();

        $output->writeln(\sprintf('<info>Container compiled for environment "%s".</info>', $env));

        return Command::SUCCESS;
    }
}
