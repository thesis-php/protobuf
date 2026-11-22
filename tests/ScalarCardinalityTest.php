<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\Reader;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\SerdeBool;
use Thesis\Protobuf\Internal\Serde\SerdeBytes;
use Thesis\Protobuf\Internal\Serde\SerdeDouble;
use Thesis\Protobuf\Internal\Serde\SerdeFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeFixed64;
use Thesis\Protobuf\Internal\Serde\SerdeFloat;
use Thesis\Protobuf\Internal\Serde\SerdeInt32;
use Thesis\Protobuf\Internal\Serde\SerdeInt64;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed64;
use Thesis\Protobuf\Internal\Serde\SerdeSInt32;
use Thesis\Protobuf\Internal\Serde\SerdeSInt64;
use Thesis\Protobuf\Internal\Serde\SerdeString;
use Thesis\Protobuf\Internal\Serde\SerdeUint32;
use Thesis\Protobuf\Internal\Serde\SerdeUint64;
use Thesis\Protobuf\Internal\Serde\SingularCodec;

#[CoversClass(SerdeBool::class)]
#[CoversClass(SerdeInt32::class)]
#[CoversClass(SerdeSInt32::class)]
#[CoversClass(SerdeUint32::class)]
#[CoversClass(SerdeInt64::class)]
#[CoversClass(SerdeSInt64::class)]
#[CoversClass(SerdeUint64::class)]
#[CoversClass(SerdeSFixed32::class)]
#[CoversClass(SerdeFixed32::class)]
#[CoversClass(SerdeSFixed64::class)]
#[CoversClass(SerdeFixed64::class)]
#[CoversClass(SerdeFloat::class)]
#[CoversClass(SerdeDouble::class)]
#[CoversClass(SerdeString::class)]
#[CoversClass(SerdeBytes::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(SingularCodec::class)]
final class ScalarCardinalityTest extends TestCase
{
    #[DataProvider('provideRoundTripCases')]
    public function testRoundTrip(ScalarTestData $data): void
    {
        $buffer = new Reader((string) hex2bin($data->hex));

        $type = $data->value->type;
        $expected = $data->value->value;

        $codec = FieldCodecs::of($type);
        self::assertInstanceOf(SingularCodec::class, $codec);

        $actual = $codec->deserializer->deserialize($buffer);

        if ($expected instanceof Number) {
            self::assertEquals($expected, $actual);
        } elseif (\is_float($expected)) {
            // The bits are compared, so -0.0 differs from 0.0 and NaN equals NaN. A float field holds a 32-bit value:
            // the expected value is the one written in the file rounded to it, e.g. -10000.2 to -10000.2001953125.
            self::assertIsFloat($actual);
            $format = $type === floatT ? 'g' : 'e';
            self::assertSame(bin2hex(pack($format, $expected)), bin2hex(pack($format, $actual)));
        } else {
            self::assertSame($expected, $actual);
        }

        self::assertCount(0, $buffer);

        $output = new ByteBuffer();
        $codec->serializer->serialize($output, $expected);
        self::assertSame($data->hex, bin2hex((string) $output));
    }

    /**
     * @return iterable<array{ScalarTestData}>
     */
    public static function provideRoundTripCases(): iterable
    {
        $f = fopen(__DIR__ . '/testdata/scalar_testcases.csv', 'r');
        if (!\is_resource($f)) {
            throw new \RuntimeException('Could not open file with testcases.');
        }

        fgetcsv($f, escape: '\\');

        while (!feof($f)) {
            /** @var false|array{non-empty-string, non-empty-string, string} $row */
            $row = fgetcsv($f, escape: '\\');
            if (!\is_array($row)) {
                break;
            }

            [$type, $hex, $value] = $row;

            yield [
                new ScalarTestData(
                    match ($type) {
                        'bool' => Value::bool(filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                        'float' => Value::float(self::toFloat($value)),
                        'double' => Value::double(self::toFloat($value)),
                        'string' => Value::string($value),
                        // The value of bytes is hex, as they need not be text.
                        'bytes' => Value::bytes((string) hex2bin($value)),
                        /** @phpstan-ignore argument.type */
                        'int32' => Value::int32(filter_var($value, FILTER_VALIDATE_INT)),
                        /** @phpstan-ignore argument.type */
                        'sint32' => Value::sint32(filter_var($value, FILTER_VALIDATE_INT)),
                        /** @phpstan-ignore argument.type */
                        'uint32' => Value::uint32(filter_var($value, FILTER_VALIDATE_INT)),
                        'int64' => Value::int64((int) $value),
                        'uint64' => Value::uint64(self::toNumber($value)),
                        'sint64' => Value::sint64((int) $value),
                        /** @phpstan-ignore argument.type */
                        'fixed32' => Value::fixed32(filter_var($value, FILTER_VALIDATE_INT)),
                        /** @phpstan-ignore argument.type */
                        'sfixed32' => Value::sfixed32(filter_var($value, FILTER_VALIDATE_INT)),
                        'fixed64' => Value::fixed64(self::toNumber($value)),
                        'sfixed64' => Value::sfixed64((int) $value),
                        default => throw new \UnexpectedValueException("Cannot handle type '{$type}'."),
                    },
                    $hex,
                ),
            ];
        }

        fclose($f);

        // The lengths at which the varint length prefix takes one more byte: too long to be written in the file.
        foreach (['7f' => 127, '8001' => 128, 'ff7f' => 16383, '808001' => 16384] as $prefix => $length) {
            $value = str_repeat('x', $length);

            yield [new ScalarTestData(Value::string($value), $prefix . bin2hex($value))];
            yield [new ScalarTestData(Value::bytes($value), $prefix . bin2hex($value))];
        }
    }

    public function testTheCodecOfATypeIsBuiltOnce(): void
    {
        $type = messageT(fieldT(1, listT(int32T)));

        self::assertSame(FieldCodecs::of($type), FieldCodecs::of($type));
        self::assertNotSame(FieldCodecs::of($type), FieldCodecs::of(messageT(fieldT(1, listT(int32T)))));
    }

    /**
     * NaN and the infinities are written as NaN, +Inf and -Inf, any other value as PHP reads a float.
     */
    private static function toFloat(string $value): float
    {
        return match ($value) {
            'NaN' => NAN,
            '+Inf' => INF,
            '-Inf' => -INF,
            default => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE)
                ?? throw new \UnexpectedValueException("'{$value}' is not a float."),
        };
    }

    private static function toNumber(string $value): Number
    {
        \assert(is_numeric($value), "'{$value}' is not a numeric value");

        return new Number($value);
    }
}

/**
 * @internal
 */
final readonly class ScalarTestData
{
    /**
     * @param Value<*> $value
     * @param non-empty-string $hex
     */
    public function __construct(
        public Value $value,
        public string $hex,
    ) {}
}
