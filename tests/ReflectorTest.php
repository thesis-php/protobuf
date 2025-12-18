<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(Reflector::class)]
final class ReflectorTest extends TestCase
{
    #[DataProvider('provideRoundTripCases')]
    public function testRoundTrip(MessageTestData $data): void
    {
        $bytes = hex2bin($data->hex);
        self::assertIsString($bytes);

        $reflector = Reflector::build();
        $serializer = new Serializer();

        $object = $reflector->map(
            $serializer->deserialize($reflector->type($data->class), $bytes),
            $data->class,
        );

        $message = $reflector->message($object);

        $hex = bin2hex($serializer->serialize($message));

        self::assertSame($data->hex, $hex);
    }

    /**
     * @return iterable<array{MessageTestData}>
     */
    public static function provideRoundTripCases(): iterable
    {
        /** @var array<non-empty-string, class-string> $requires */
        static $requires = [];

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

            if (!isset($requires[$path])) {
                /** @var class-string $class */
                $class = require_once $path;

                $requires[$path] = $class;
            }

            yield [
                new MessageTestData(
                    $requires[$path],
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
     * @param class-string $class
     * @param non-empty-string $hex
     */
    public function __construct(
        public string $class,
        public string $hex,
    ) {}
}
