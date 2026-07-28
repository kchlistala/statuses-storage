<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Internal;

use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Jobs;

final class JobsFactoryFactory
{
    public static function create(): Jobs
    {
        $rpcAddress = Environment::fromGlobals()->getRPCAddress();

        if ('' === $rpcAddress) {
            throw new \RuntimeException('RoadRunner RPC address is not set (RR_RPC_ADDRESS).');
        }

        return new Jobs(RPC::create($rpcAddress));
    }
}
