<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Public;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * Routes a consumed Kafka task to the registered {@see JobHandlerInterface}. Exposed here (rather
 * than only the Internal implementation) so App\Kernel can depend on it without reaching into
 * Shared\Kafka\Internal.
 */
interface JobDispatcherInterface
{
    public function dispatch(ReceivedTaskInterface $task): void;
}
