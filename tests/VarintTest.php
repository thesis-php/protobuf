<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Internal\Buffer\Reader;

#[CoversClass(Reader::class)]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\writeVarint')]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\toUnsigned')]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\fromUnsigned')]
final class VarintTest extends TestCase
{
    /**
     * @param Type<mixed, *, *, *> $type
     * @param non-empty-string $hex the field value, after the tag
     */
    #[DataProvider('provideVarintsAreReadAsProtobufReadsThemCases')]
    public function testVarintsAreReadAsProtobufReadsThem(Type $type, string $hex, mixed $expected): void
    {
        $message = new Serializer()->deserialize(messageT(fieldT(1, $type)), (string) hex2bin("08{$hex}"));

        self::assertEquals($expected, ($message->fields[1] ?? null)?->value->value);
    }

    /**
     * @return iterable<string, array{Type<mixed, *, *, *>, non-empty-string, mixed}>
     */
    public static function provideVarintsAreReadAsProtobufReadsThemCases(): iterable
    {
        // The 10th byte carries only bit 63: higher bits are dropped, as official parsers do.
        yield 'uint64 above 2^64-1 is truncated' => [uint64T, 'ffffffffffffffffff7f', new Number('18446744073709551615')];
        yield 'int64 above 2^64-1 is truncated' => [int64T, 'ffffffffffffffffff7f', -1];
        yield 'uint64 2^63' => [uint64T, '80808080808080808001', new Number('9223372036854775808')];
        // Non-canonical forms with redundant continuation bytes are valid.
        yield 'overlong zero' => [int32T, '8080808000', 0];
        yield 'overlong one' => [uint64T, '818000', new Number(1)];
        // 32-bit types take the low 32 bits of whatever the varint holds.
        yield 'int32 from a 5-byte -1' => [int32T, 'ffffffff0f', -1];
        yield 'uint32 from a 10-byte value' => [uint32T, 'ffffffffffffffffff01', 4294967295];
        yield 'sint32 from a 10-byte value' => [sint32T, 'feffffffffffffffff01', 2147483647];
        yield 'bool from a large value' => [boolT, '80808080808080808001', true];
    }

    public function testVarintEndsAtTheLimitOfTheNestedMessage(): void
    {
        // Field 1 is a 1-byte message holding only a tag; the value byte after it belongs to the outer message.
        $type = messageT(fieldT(1, messageT(fieldT(1, int32T))), fieldT(2, int32T));

        $this->expectException(BufferUnderflow::class);
        $this->expectExceptionMessage('truncated varint');

        new Serializer()->deserialize($type, "\x0a\x01\x08\x01");
    }

    /**
     * @param non-empty-string $hex
     */
    #[DataProvider('provideLengthsOf2To63AndAboveAreReportedAsTooLongCases')]
    public function testLengthsOf2To63AndAboveAreReportedAsTooLong(string $hex): void
    {
        $type = messageT(fieldT(1, messageT()), fieldT(2, listT(int32T)), fieldT(3, stringT));

        $this->expectException(BufferUnderflow::class);
        $this->expectExceptionMessage(\sprintf("Expected '%d' bytes", PHP_INT_MAX));

        new Serializer()->deserialize($type, (string) hex2bin($hex));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideLengthsOf2To63AndAboveAreReportedAsTooLongCases(): iterable
    {
        yield 'nested message' => ['0a8080808080808080800100'];
        yield 'packed list' => ['128080808080808080800100'];
        yield 'string' => ['1a8080808080808080800100'];
        yield 'unknown field' => ['228080808080808080800100'];
    }

    public function testUnknownVarintsKeepTheirUnsignedValue(): void
    {
        $message = new Serializer()->deserialize(messageT(), (string) hex2bin('08fbffffffffffffffff01'));

        self::assertEquals(new Number('18446744073709551611'), $message->unknowns[0]->value ?? null);
    }

    /**
     * @param FieldDescriptor<*> $field
     * @param non-empty-string $hex
     */
    #[DataProvider('provideValuesAreWrittenAsCanonicalVarintsCases')]
    public function testValuesAreWrittenAsCanonicalVarints(FieldDescriptor $field, string $hex): void
    {
        self::assertSame($hex, bin2hex(new Serializer()->serialize(message($field))));
    }

    /**
     * @return iterable<string, array{FieldDescriptor<*>, non-empty-string}>
     */
    public static function provideValuesAreWrittenAsCanonicalVarintsCases(): iterable
    {
        yield '127 takes one byte' => [fieldOf(1, int64Of(127)), '087f'];
        yield '128 takes two bytes' => [fieldOf(1, int64Of(128)), '088001'];
        yield 'int64 max' => [fieldOf(1, int64Of(PHP_INT_MAX)), '08ffffffffffffffff7f'];
        yield 'int64 min' => [fieldOf(1, int64Of(PHP_INT_MIN)), '0880808080808080808001'];
        yield 'uint64 2^63' => [fieldOf(1, uint64Of(new Number('9223372036854775808'))), '0880808080808080808001'];
        yield 'sint64 min' => [fieldOf(1, sint64Of(PHP_INT_MIN)), '08ffffffffffffffffff01'];
        yield 'field 2^29-1' => [fieldOf(536_870_911, boolOf(true)), 'f8ffffff0f01'];
    }
}
