<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ByteStream::class)]
final class ByteStreamTest extends TestCase
{
    #[DataProvider('provideRoundTripCases')]
    public function testRoundTrip(TestData $data): void
    {
        switch ($data->type) {
            case Type::bool:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): bool => $stream->readBool(),
                    static fn(ByteStream $stream, bool $value): ByteStream => $stream->writeBool($value),
                );

                break;
            case Type::int32:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readInt32(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeInt32($num),
                );

                break;
            case Type::sint32:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readSInt32(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeSInt32($num),
                );

                break;
            case Type::uint32:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readUint32(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeUint32($num),
                );

                break;
            case Type::int64:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readInt64(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeInt64($num),
                );

                break;
            case Type::sint64:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readSInt64(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeSInt64($num),
                );

                break;
            case Type::uint64:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readUint64(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeUint64($num),
                );

                break;
            case Type::sfixed32:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): int => $stream->readSFixed32(),
                    static fn(ByteStream $stream, int $num): ByteStream => $stream->writeSFixed32($num),
                );

                break;
            case Type::fixed32:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): int => $stream->readFixed32(),
                    static fn(ByteStream $stream, int $num): ByteStream => $stream->writeFixed32($num),
                );

                break;
            case Type::sfixed64:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readSFixed64(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeSFixed64($num),
                );

                break;
            case Type::fixed64:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): Number => $stream->readFixed64(),
                    static fn(ByteStream $stream, Number $num): ByteStream => $stream->writeFixed64($num),
                );

                break;
            case Type::float:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): float => $stream->readFloat(),
                    static fn(ByteStream $stream, float $num): ByteStream => $stream->writeFloat($num),
                );

                break;
            case Type::double:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): float => $stream->readDouble(),
                    static fn(ByteStream $stream, float $num): ByteStream => $stream->writeDouble($num),
                );

                break;
            case Type::string:
                self::assertValidProtobuf(
                    $data,
                    static fn(ByteStream $stream): string => $stream->readString(),
                    static fn(ByteStream $stream, string $value): ByteStream => $stream->writeString($value),
                );

                break;
        }
    }

    /**
     * @return iterable<array{TestData}>
     */
    public static function provideRoundTripCases(): iterable
    {
        $f = fopen(__DIR__ . '/testdata/testcases.csv', 'r');
        if (!\is_resource($f)) {
            throw new \RuntimeException('Could not open file with testcases.');
        }

        fgetcsv($f, escape: '\\');

        while (!feof($f)) {
            /** @var false|array{non-empty-string, non-empty-string, non-empty-string} $row */
            $row = fgetcsv($f, escape: '\\');
            if (!\is_array($row)) {
                break;
            }

            [$type, $hex, $value] = $row;

            yield [
                new TestData(
                    $type = Type::from($type),
                    $hex,
                    match ($type) {
                        Type::bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                        Type::float,
                        Type::double => filter_var($value, FILTER_VALIDATE_FLOAT),
                        Type::string => $value,
                        Type::fixed32,
                        Type::sfixed32 => filter_var($value, FILTER_VALIDATE_INT),
                        default => new Number($value),
                    },
                ),
            ];
        }

        fclose($f);
    }

    /**
     * @template T
     * @param \Closure(ByteStream): T $read
     * @param \Closure(ByteStream, T): ByteStream $write
     */
    private static function assertValidProtobuf(
        TestData $data,
        \Closure $read,
        \Closure $write,
    ): void {
        $buffer = new ByteBuffer((string) hex2bin($data->hex));
        $stream = new ByteStream($buffer);

        /** @var T $expected */
        $expected = $data->value;
        $actual = $read($stream);

        if ($expected instanceof Number) {
            self::assertEquals($expected, $actual);
        } elseif (\is_float($expected)) {
            /** @phpstan-ignore argument.type */
            self::assertSame(round($expected, 2), round($actual, 2));
        } else {
            self::assertSame($expected, $actual);
        }

        self::assertCount(0, $buffer);

        $write($stream, $expected);
        self::assertSame($data->hex, bin2hex((string) $buffer));
    }
}

/**
 * @internal
 */
final readonly class TestData
{
    /**
     * @param non-empty-string $hex
     */
    public function __construct(
        public Type $type,
        public string $hex,
        public mixed $value,
    ) {}
}
