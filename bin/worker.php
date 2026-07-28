#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Kernel\ContainerFactory;
use App\Kernel\Http\HttpWorkerRunner;
use App\Kernel\Jobs\JobsWorkerRunner;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Environment\Mode;
use Symfony\Component\Dotenv\Dotenv;

$projectDir = \dirname(__DIR__);

(new Dotenv())->bootEnv($projectDir . '/.env');

$container = (new ContainerFactory(
    projectDir: $projectDir,
    environment: $_SERVER['APP_ENV'] ?? 'prod',
    debug: (bool) ($_SERVER['APP_DEBUG'] ?? false),
))->create();

$mode = Environment::fromGlobals()->getMode();

match ($mode) {
    Mode::MODE_HTTP => $container->get(HttpWorkerRunner::class)->run(),
    Mode::MODE_JOBS => $container->get(JobsWorkerRunner::class)->run(),
    default => throw new \RuntimeException(\sprintf('Unsupported RoadRunner worker mode: "%s"', $mode)),
};
