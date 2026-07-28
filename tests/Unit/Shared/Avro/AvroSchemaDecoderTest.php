<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Avro;

use App\Shared\Avro\Internal\AvroSchemaDecoder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class AvroSchemaDecoderTest extends TestCase
{
    private const string SCHEMA_JSON = <<<'JSON'
        {
          "type": "record",
          "name": "Member",
          "fields": [
            {"name": "memberId", "type": "int"},
            {"name": "memberName", "type": "string"}
          ]
        }
        JSON;

    public function testDecodesBinaryPayloadEncodedAgainstTheSameSchema(): void
    {
        $datum = ['memberId' => 1392, 'memberName' => 'Jose'];
        $binaryPayload = $this->encode($datum);

        $decoder = new AvroSchemaDecoder();

        self::assertSame($datum, $decoder->decode($binaryPayload, self::SCHEMA_JSON));
    }

    /**
     * @param array<string, mixed> $datum
     */
    private function encode(array $datum): string
    {
        $schema = \AvroSchema::parse(self::SCHEMA_JSON);
        $io = new \AvroStringIO();
        $encoder = new \AvroIOBinaryEncoder($io);
        $writer = new \AvroIODatumWriter($schema);

        $writer->write($datum, $encoder);

        return $io->string();
    }
}
