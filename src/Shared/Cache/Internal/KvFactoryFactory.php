<?php

declare(strict_types=1);

namespace App\Shared\Cache\Internal;

use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\KeyValue\Factory;

final class KvFactoryFactory
{
    public static function create(): Factory
    {
        $rpcAddress = Environment::fromGlobals()->getRPCAddress();

        if ('' === $rpcAddress) {
            throw new \RuntimeException('RoadRunner RPC address is not set (RR_RPC_ADDRESS).');
        }

        return new Factory(RPC::create($rpcAddress));
    }
}
