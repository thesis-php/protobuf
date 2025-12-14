<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\DetermineWireType;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\TypeDeserializerVisitor;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\TypeSerializerVisitor;
use Thesis\Protobuf\Internal\Serde\SerdeBool;
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
use Thesis\Protobuf\Internal\Serde\SerializeTag;
use Thesis\Protobuf\Internal\Wire\Tag;

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
final class ScalarCardinalityTest extends TestCase
{
    #[DataProvider('provideRoundTripCases')]
    public function testRoundTrip(ScalarTestData $data): void
    {
        $buffer = new ByteBuffer((string) hex2bin($data->hex));

        $type = $data->value->type;
        $expected = $data->value->value;

        $tag = new Tag(1, $type->accept(DetermineWireType::Visitor));

        $actual = $type
            ->accept(new TypeDeserializerVisitor($tag))
            ->deserialize($buffer);

        if ($expected instanceof Number) {
            self::assertEquals($expected, $actual);
        } elseif (\is_float($expected)) {
            /** @phpstan-ignore argument.type */
            self::assertSame(round($expected, 2), round($actual, 2));
        } else {
            self::assertSame($expected, $actual);
        }

        self::assertCount(0, $buffer);

        $type
            ->accept(new TypeSerializerVisitor($tag))
            ->without(SerializeTag::class)
            ->serialize($buffer, $expected);
        self::assertSame($data->hex, bin2hex((string) $buffer));
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
            /** @var false|array{non-empty-string, non-empty-string, non-empty-string} $row */
            $row = fgetcsv($f, escape: '\\');
            if (!\is_array($row)) {
                break;
            }

            [$type, $hex, $value] = $row;

            yield [
                new ScalarTestData(
                    match ($type) {
                        'bool' => Value::bool(filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                        /** @phpstan-ignore argument.type */
                        'float' => Value::float(filter_var($value, FILTER_VALIDATE_FLOAT)),
                        /** @phpstan-ignore argument.type */
                        'double' => Value::double(filter_var($value, FILTER_VALIDATE_FLOAT)),
                        'string' => Value::string($value),
                        /** @phpstan-ignore argument.type */
                        'int32' => Value::int32(filter_var($value, FILTER_VALIDATE_INT)),
                        /** @phpstan-ignore argument.type */
                        'sint32' => Value::sint32(filter_var($value, FILTER_VALIDATE_INT)),
                        /** @phpstan-ignore argument.type */
                        'uint32' => Value::uint32(filter_var($value, FILTER_VALIDATE_INT)),
                        'int64' => Value::int64(new Number($value)),
                        'uint64' => Value::uint64(new Number($value)),
                        'sint64' => Value::sint64(new Number($value)),
                        /** @phpstan-ignore argument.type */
                        'fixed32' => Value::fixed32(filter_var($value, FILTER_VALIDATE_INT)),
                        /** @phpstan-ignore argument.type */
                        'sfixed32' => Value::sfixed32(filter_var($value, FILTER_VALIDATE_INT)),
                        'fixed64' => Value::fixed64(new Number($value)),
                        'sfixed64' => Value::sfixed64(new Number($value)),
                        default => throw new \UnexpectedValueException("Cannot handle type '{$type}'."),
                    },
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
