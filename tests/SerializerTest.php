<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Schema\Type\MessageT;

#[CoversClass(Serializer::class)]
final class SerializerTest extends TestCase
{
    #[DataProvider('provideRoundTripCases')]
    public function testRoundTrip(MessageTestData $data): void
    {
        $bytes = hex2bin($data->hex);
        self::assertIsString($bytes);

        $serializer = new Serializer();

        $message = $serializer->deserialize($data->type, $bytes);

        $hex = bin2hex($serializer->serialize($message));

        self::assertSame($data->hex, $hex);
    }

    /**
     * @return iterable<array{MessageTestData}>
     */
    public static function provideRoundTripCases(): iterable
    {
        $f = fopen(__DIR__ . '/testdata/message_testcases.csv', 'r');
        if (!\is_resource($f)) {
            throw new \RuntimeException('Could not open file with testcases.');
        }

        fgetcsv($f, escape: '\\');

        while (!feof($f)) {
            /** @var false|array{non-empty-string, non-empty-string} $row */
            $row = fgetcsv($f, escape: '\\');
            if (!\is_array($row)) {
                break;
            }

            [$hex, $path] = $row;

            /** @var MessageT $messageT */
            $messageT = require_once $path;

            yield [
                new MessageTestData(
                    $messageT,
                    $hex,
                ),
            ];
        }

        fclose($f);
    }
}

/**
 * @internal
 */
final readonly class MessageTestData
{
    /**
     * @param non-empty-string $hex
     */
    public function __construct(
        public MessageT $type,
        public string $hex,
    ) {}
}
