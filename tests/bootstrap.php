<?php

declare(strict_types=1);
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$projectDir = dirname(__DIR__);

if (is_file($projectDir.'/.env') || is_file($projectDir.'/.env.dist')) {
    (new Dotenv())->bootEnv($projectDir.'/.env');
}
