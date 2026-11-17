<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Serde\Range;
use Thesis\Protobuf\Internal\Serde\SerdeFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeFixed64;
use Thesis\Protobuf\Internal\Serde\SerdeInt32;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeSInt32;
use Thesis\Protobuf\Internal\Serde\SerdeUint32;
use Thesis\Protobuf\Internal\Serde\SerdeUint64;
use Thesis\Protobuf\Internal\Serde\SerializeEnum;

#[CoversClass(ValueOutOfRange::class)]
#[CoversClass(Range::class)]
#[CoversClass(SerdeInt32::class)]
#[CoversClass(SerdeSInt32::class)]
#[CoversClass(SerdeUint32::class)]
#[CoversClass(SerdeUint64::class)]
#[CoversClass(SerdeFixed32::class)]
#[CoversClass(SerdeSFixed32::class)]
#[CoversClass(SerdeFixed64::class)]
#[CoversClass(SerializeEnum::class)]
final class ValueRangeTest extends TestCase
{
    /**
     * @param FieldDescriptor<*> $field
     * @param non-empty-string $type
     */
    #[DataProvider('provideOutOfRangeValuesAreRejectedCases')]
    public function testOutOfRangeValuesAreRejected(FieldDescriptor $field, string $type, string $shown): void
    {
        try {
            new Serializer()->serialize(message($field));
            self::fail('Serialization must fail.');
        } catch (ValueOutOfRange $e) {
            self::assertSame($type, $e->type);
            self::assertSame($shown, (string) $e->value);
            self::assertStringContainsString("{$shown} is out of range for {$type}", $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{FieldDescriptor<*>, non-empty-string, string}>
     */
    public static function provideOutOfRangeValuesAreRejectedCases(): iterable
    {
        yield 'int32 above max' => [fieldOf(1, int32Of(2 ** 31)), 'int32', '2147483648'];
        yield 'int32 below min' => [fieldOf(1, int32Of(-2 ** 31 - 1)), 'int32', '-2147483649'];
        yield 'sint32 above max' => [fieldOf(1, sint32Of(2 ** 31)), 'sint32', '2147483648'];
        yield 'sint32 below min' => [fieldOf(1, sint32Of(-2 ** 31 - 1)), 'sint32', '-2147483649'];
        yield 'sfixed32 above max' => [fieldOf(1, sfixed32Of(2 ** 31)), 'sfixed32', '2147483648'];
        yield 'sfixed32 below min' => [fieldOf(1, sfixed32Of(-2 ** 31 - 1)), 'sfixed32', '-2147483649'];
        yield 'uint32 negative' => [fieldOf(1, uint32Of(-1)), 'uint32', '-1'];
        yield 'uint32 above max' => [fieldOf(1, uint32Of(2 ** 32)), 'uint32', '4294967296'];
        yield 'fixed32 negative' => [fieldOf(1, fixed32Of(-1)), 'fixed32', '-1'];
        yield 'fixed32 above max' => [fieldOf(1, fixed32Of(2 ** 32)), 'fixed32', '4294967296'];
        yield 'uint64 negative' => [fieldOf(1, uint64Of(new Number(-1))), 'uint64', '-1'];
        yield 'uint64 above max' => [fieldOf(1, uint64Of(new Number('18446744073709551616'))), 'uint64', '18446744073709551616'];
        yield 'uint64 fractional' => [fieldOf(1, uint64Of(new Number('1.5'))), 'uint64', '1.5'];
        yield 'fixed64 negative' => [fieldOf(1, fixed64Of(new Number(-1))), 'fixed64', '-1'];
        yield 'fixed64 above max' => [fieldOf(1, fixed64Of(new Number('18446744073709551616'))), 'fixed64', '18446744073709551616'];
        yield 'fixed64 fractional' => [fieldOf(1, fixed64Of(new Number('1.5'))), 'fixed64', '1.5'];
        yield 'enum above max' => [fieldOf(1, enumOf(ValueRangeTestEnum::TooBig)), 'enum', '2147483648'];
        yield 'enum below min' => [fieldOf(1, enumOf(ValueRangeTestEnum::TooSmall)), 'enum', '-2147483649'];
        yield 'packed list element' => [fieldOf(1, listOf(int32T, [1, 2 ** 31], true)), 'int32', '2147483648'];
        yield 'unpacked list element' => [fieldOf(1, listOf(uint32T, [1, -1], false)), 'uint32', '-1'];
        yield 'map key' => [fieldOf(1, mapOf(uint32T, stringT, Map::fromArray([-1 => 'a']))), 'uint32', '-1'];
        yield 'map value' => [fieldOf(1, mapOf(stringT, sint32T, Map::fromArray(['a' => 2 ** 31]))), 'sint32', '2147483648'];
    }

    /**
     * @param FieldDescriptor<*> $field
     * @param non-empty-string $hex
     */
    #[DataProvider('provideBoundaryValuesAreEncodedCases')]
    public function testBoundaryValuesAreEncoded(FieldDescriptor $field, string $hex): void
    {
        self::assertSame($hex, bin2hex(new Serializer()->serialize(message($field))));
    }

    /**
     * @return iterable<string, array{FieldDescriptor<*>, non-empty-string}>
     */
    public static function provideBoundaryValuesAreEncodedCases(): iterable
    {
        yield 'int32 max' => [fieldOf(1, int32Of(2 ** 31 - 1)), '08ffffffff07'];
        yield 'int32 min' => [fieldOf(1, int32Of(-2 ** 31)), '0880808080f8ffffffff01'];
        yield 'sint32 max' => [fieldOf(1, sint32Of(2 ** 31 - 1)), '08feffffff0f'];
        yield 'sint32 min' => [fieldOf(1, sint32Of(-2 ** 31)), '08ffffffff0f'];
        yield 'sfixed32 max' => [fieldOf(1, sfixed32Of(2 ** 31 - 1)), '0dffffff7f'];
        yield 'sfixed32 min' => [fieldOf(1, sfixed32Of(-2 ** 31)), '0d00000080'];
        yield 'uint32 max' => [fieldOf(1, uint32Of(2 ** 32 - 1)), '08ffffffff0f'];
        yield 'fixed32 max' => [fieldOf(1, fixed32Of(2 ** 32 - 1)), '0dffffffff'];
        yield 'uint64 max' => [fieldOf(1, uint64Of(new Number('18446744073709551615'))), '08ffffffffffffffffff01'];
        yield 'uint64 integral with a scale' => [fieldOf(1, uint64Of(new Number('5.0'))), '0805'];
        yield 'fixed64 max' => [fieldOf(1, fixed64Of(new Number('18446744073709551615'))), '09ffffffffffffffff'];
        yield 'fixed64 integral with a scale' => [fieldOf(1, fixed64Of(new Number('5.0'))), '090500000000000000'];
        yield 'enum -1 is sign-extended' => [fieldOf(1, enumOf(ValueRangeTestEnum::MinusOne)), '08ffffffffffffffffff01'];
        yield 'enum int32 min' => [fieldOf(1, enumOf(ValueRangeTestEnum::Min)), '0880808080f8ffffffff01'];
        yield 'enum int32 max' => [fieldOf(1, enumOf(ValueRangeTestEnum::Max)), '08ffffffff07'];
    }

    public function testNegativeEnumRoundTrips(): void
    {
        $serializer = new Serializer();
        $bytes = $serializer->serialize(message(fieldOf(1, enumOf(ValueRangeTestEnum::MinusOne))));

        $decoded = $serializer->deserialize(messageT(fieldT(1, enumT(ValueRangeTestEnum::class))), $bytes);

        self::assertSame(ValueRangeTestEnum::MinusOne, ($decoded->fields[1] ?? null)?->value->value);
    }

    public function testStringBackedEnumIsRejected(): void
    {
        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessage(\sprintf('Enum "%s" must be int-backed', ValueRangeTestStringEnum::class));

        new Serializer()->serialize(message(fieldOf(1, enumOf(ValueRangeTestStringEnum::A))));
    }

    public function testEncoderReportsOutOfRangeValues(): void
    {
        $this->expectException(ValueOutOfRange::class);

        Encoder\Builder::buildDefault()->encode(new ValueRangeTestMessage(2 ** 31));
    }
}

enum ValueRangeTestEnum: int
{
    case Zero = 0;
    case MinusOne = -1;
    case Min = -2147483648;
    case Max = 2147483647;
    case TooBig = 2147483648;
    case TooSmall = -2147483649;
}

enum ValueRangeTestStringEnum: string
{
    case A = 'a';
}

final readonly class ValueRangeTestMessage
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}
