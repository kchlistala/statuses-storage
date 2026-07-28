<?php

declare(strict_types=1);

namespace App\Shared\Avro\Public;

/**
 * Decodes an Avro binary payload against a locally embedded schema (no schema registry).
 */
interface AvroDecoderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function decode(string $binaryPayload, string $schemaJson): array;
}
