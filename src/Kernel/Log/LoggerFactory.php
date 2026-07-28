<?php

declare(strict_types=1);

namespace App\Kernel\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(): LoggerInterface
    {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://stderr', Level::Info));

        return $logger;
    }
}
