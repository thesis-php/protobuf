<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Serde\ListCodec;
use Thesis\Protobuf\Internal\Serde\SerdeDouble;
use Thesis\Protobuf\Internal\Serde\SerdeFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeFixed64;
use Thesis\Protobuf\Internal\Serde\SerdeFloat;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed64;

#[CoversClass(ListCodec::class)]
#[CoversClass(SerdeFixed32::class)]
#[CoversClass(SerdeSFixed32::class)]
#[CoversClass(SerdeFixed64::class)]
#[CoversClass(SerdeSFixed64::class)]
#[CoversClass(SerdeFloat::class)]
#[CoversClass(SerdeDouble::class)]
final class PackedFixedWidthTest extends TestCase
{
    /**
     * @param Type<mixed, 'repeatable', *, 'map-value'> $type
     * @param list<mixed> $values
     * @param list<mixed> $read
     */
    #[DataProvider('providePackedElementsOfConstantWidthAreWrittenAndReadCases')]
    public function testPackedElementsOfConstantWidthAreWrittenAndRead(Type $type, array $values, string $hex, array $read): void
    {
        $serializer = new Serializer();

        self::assertSame($hex, bin2hex($serializer->serialize(message(fieldOf(1, listOf($type, $values, true))))));
        self::assertSame($read, self::values($serializer->deserialize(messageT(fieldT(1, listT($type))), (string) hex2bin($hex))));
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, 'map-value'>, list<mixed>, string, list<mixed>}>
     */
    public static function providePackedElementsOfConstantWidthAreWrittenAndReadCases(): iterable
    {
        yield 'fixed32' => [fixed32T, [0, 1, 4_294_967_295], '0a0c0000000001000000ffffffff', [0, 1, 4_294_967_295]];
        yield 'sfixed32' => [
            sfixed32T,
            [-2_147_483_648, -1, 2_147_483_647],
            '0a0c00000080ffffffffffffff7f',
            [-2_147_483_648, -1, 2_147_483_647],
        ];
        yield 'fixed64' => [
            fixed64T,
            [new Number(0), new Number(1), new Number('18446744073709551615')],
            '0a1800000000000000000100000000000000ffffffffffffffff',
            ['0', '1', '18446744073709551615'],
        ];
        yield 'sfixed64' => [
            sfixed64T,
            [PHP_INT_MIN, -1, PHP_INT_MAX],
            '0a180000000000000080ffffffffffffffffffffffffffffff7f',
            [PHP_INT_MIN, -1, PHP_INT_MAX],
        ];
        // A float has 32 bits: 0.1 is read as the float closest to it.
        yield 'float' => [floatT, [1.5, -2.0, 0.1], '0a0c0000c03f000000c0cdcccc3d', [1.5, -2.0, 0.10000000149011612]];
        yield 'double' => [doubleT, [1.5, -0.1], '0a10000000000000f83f9a9999999999b9bf', [1.5, -0.1]];
        yield 'an int as a float' => [floatT, [1.5, 2], '0a080000c03f00000040', [1.5, 2.0]];
        yield 'an int as a double' => [doubleT, [2], '0a080000000000000040', [2.0]];
    }

    /**
     * @param Type<mixed, 'repeatable', *, 'map-value'> $type
     */
    #[DataProvider('provideAListWithoutElementsIsNotWrittenCases')]
    public function testAListWithoutElementsIsNotWritten(Type $type): void
    {
        self::assertSame('', new Serializer()->serialize(message(fieldOf(1, listOf($type, [], true)))));
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, 'map-value'>}>
     */
    public static function provideAListWithoutElementsIsNotWrittenCases(): iterable
    {
        yield 'fixed32' => [fixed32T];
        yield 'sfixed64' => [sfixed64T];
        yield 'double' => [doubleT];
    }

    public function testTheElementsOfAnArrayWithAnyKeysAreWrittenInOrder(): void
    {
        // A list property may hold any array.
        $values = [3 => 1.5, 'a' => -2.0, 1 => 0.5];

        self::assertSame(
            '0a0c0000c03f000000c00000003f',
            /** @phpstan-ignore argument.type */
            bin2hex(new Serializer()->serialize(message(fieldOf(1, listOf(floatT, $values, true))))),
        );
    }

    /**
     * @param Type<mixed, 'repeatable', *, 'map-value'> $type
     * @param list<mixed> $read
     */
    #[DataProvider('provideOccurrencesOfAPackedFieldAreConcatenatedCases')]
    public function testOccurrencesOfAPackedFieldAreConcatenated(Type $type, string $hex, array $read): void
    {
        self::assertSame($read, self::values(new Serializer()->deserialize(
            messageT(fieldT(1, listT($type)), fieldT(2, int32T)),
            (string) hex2bin($hex),
        )));
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, 'map-value'>, string, list<mixed>}>
     */
    public static function provideOccurrencesOfAPackedFieldAreConcatenatedCases(): iterable
    {
        yield 'fixed32 packed twice' => [fixed32T, '0a08010000000200000010070a0403000000', [1, 2, 3]];
        yield 'fixed32 unpacked, then packed' => [fixed32T, '0d010000000a080200000003000000', [1, 2, 3]];
        yield 'double packed twice' => [doubleT, '0a08000000000000f03f0a080000000000000040', [1.0, 2.0]];
        yield 'an empty record between' => [sfixed32T, '0a04ffffffff0a000a0402000000', [-1, 2]];
    }

    public function testManyPackedRecordsOfAFieldAreConcatenatedInOrder(): void
    {
        $bytes = '';

        for ($i = 0; $i < 1000; ++$i) {
            $bytes .= "\x0a\x08" . pack('VV', 2 * $i, 2 * $i + 1);
        }

        self::assertSame(range(0, 1999), self::values(new Serializer()->deserialize(messageT(fieldT(1, listT(fixed32T))), $bytes)));
    }

    /**
     * @param Type<mixed, 'repeatable', *, 'map-value'> $type
     * @param non-empty-string $message
     */
    #[DataProvider('provideAnElementCutShortFailsAsWhenReadAloneCases')]
    public function testAnElementCutShortFailsAsWhenReadAlone(Type $type, string $hex, string $message): void
    {
        $this->expectExceptionObject(new BufferUnderflow($message));

        new Serializer()->deserialize(messageT(fieldT(1, listT($type))), (string) hex2bin($hex));
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, 'map-value'>, string, non-empty-string}>
     */
    public static function provideAnElementCutShortFailsAsWhenReadAloneCases(): iterable
    {
        yield 'fixed32 after a whole one' => [fixed32T, '0a06010000000200', "Expected '4' bytes, but the buffer only has '2' bytes."];
        yield 'fixed32 alone' => [fixed32T, '0a03010000', "Expected '4' bytes, but the buffer only has '3' bytes."];
        yield 'double after a whole one' => [doubleT, '0a0b000000000000f03f000000', "Expected '8' bytes, but the buffer only has '3' bytes."];
        yield 'fixed64 alone' => [fixed64T, '0a0101', "Expected '8' bytes, but the buffer only has '1' bytes."];
    }

    /**
     * @param Type<mixed, 'repeatable', *, 'map-value'> $type
     * @param list<mixed> $values
     * @param non-empty-string $shown
     */
    #[DataProvider('provideAnElementOutOfRangeFailsAsWhenWrittenAloneCases')]
    public function testAnElementOutOfRangeFailsAsWhenWrittenAlone(Type $type, array $values, string $shown): void
    {
        $this->expectException(ValueOutOfRange::class);
        $this->expectExceptionMessage("{$shown} is out of range");

        new Serializer()->serialize(message(fieldOf(1, listOf($type, $values, true))));
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, 'map-value'>, list<mixed>, non-empty-string}>
     */
    public static function provideAnElementOutOfRangeFailsAsWhenWrittenAloneCases(): iterable
    {
        yield 'fixed32 negative' => [fixed32T, [1, -1], '-1'];
        yield 'fixed32 above max' => [fixed32T, [4_294_967_296], '4294967296'];
        yield 'sfixed32 below min' => [sfixed32T, [-2_147_483_649, 1], '-2147483649'];
        yield 'sfixed32 above max' => [sfixed32T, [2_147_483_648], '2147483648'];
        yield 'fixed64 negative' => [fixed64T, [new Number(1), new Number(-1)], '-1'];
    }

    /**
     * @param Type<mixed, 'repeatable', *, 'map-value'> $type
     * @param list<mixed> $values
     */
    #[DataProvider('provideAnElementThatIsNotAFloatFailsAsWhenWrittenAloneCases')]
    public function testAnElementThatIsNotAFloatFailsAsWhenWrittenAlone(Type $type, array $values): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type float, string given');

        new Serializer()->serialize(message(fieldOf(1, listOf($type, $values, true))));
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, 'map-value'>, list<mixed>}>
     */
    public static function provideAnElementThatIsNotAFloatFailsAsWhenWrittenAloneCases(): iterable
    {
        yield 'float' => [floatT, ['2.5']];
        yield 'float after a float' => [floatT, [1.5, '2.5']];
        yield 'double' => [doubleT, ['2.5']];
    }

    /**
     * @return list<mixed>
     */
    private static function values(Message $message): array
    {
        /** @var list<mixed> $values */
        $values = $message->fields[1]->value->value ?? [];

        return array_map(static fn(mixed $value): mixed => $value instanceof Number ? (string) $value : $value, $values);
    }
}
