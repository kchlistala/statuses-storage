<?php

declare(strict_types=1);

namespace App\Shared\Kafka\Public;

interface ProducerInterface
{
    public function publish(KafkaMessage $message): void;
}
