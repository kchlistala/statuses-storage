<?php

declare(strict_types=1);

namespace App\Shared\Avro\Internal;

use App\Shared\Avro\Public\AvroDecoderInterface;

final readonly class AvroSchemaDecoder implements AvroDecoderInterface
{
    #[\Override]
    public function decode(string $binaryPayload, string $schemaJson): array
    {
        $schema = \AvroSchema::parse($schemaJson);
        $io = new \AvroStringIO($binaryPayload);
        $decoder = new \AvroIOBinaryDecoder($io);
        $reader = new \AvroIODatumReader($schema);

        $datum = $reader->read($decoder);

        if (!\is_array($datum)) {
            throw new \RuntimeException('Decoded Avro datum is not an array.');
        }

        /** @var array<string, mixed> $datum */
        return $datum;
    }
}
