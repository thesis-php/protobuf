<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\UnknownFields\UnknownField;

#[CoversClass(Serializer::class)]
#[CoversClass(MessageFields::class)]
final class UnknownFieldsSerializeTest extends TestCase
{
    /**
     * @param non-empty-string $expected the hex of the unknown field written after field 1
     */
    #[DataProvider('provideAnUnknownFieldIsWrittenAfterTheKnownFieldsCases')]
    public function testAnUnknownFieldIsWrittenAfterTheKnownFields(UnknownField $unknown, string $expected): void
    {
        $bytes = new Serializer()->serialize(new Message([fieldOf(1, int32Of(5))], [$unknown]));

        self::assertSame('0805' . $expected, bin2hex($bytes));
    }

    /**
     * @return iterable<string, array{UnknownField, non-empty-string}>
     */
    public static function provideAnUnknownFieldIsWrittenAfterTheKnownFieldsCases(): iterable
    {
        yield 'varint' => [new UnknownField(new Tag(2, WireType::VARINT), new Number(150)), '109601'];
        yield 'varint of zero' => [new UnknownField(new Tag(2, WireType::VARINT), new Number(0)), '1000'];
        yield 'varint of 2^64-1' => [
            new UnknownField(new Tag(2, WireType::VARINT), new Number('18446744073709551615')),
            '10ffffffffffffffffff01',
        ];
        yield 'fixed32' => [new UnknownField(new Tag(3, WireType::FIXED32), "\x01\x02\x03\x04"), '1d01020304'];
        yield 'fixed64' => [
            new UnknownField(new Tag(3, WireType::FIXED64), "\x01\x02\x03\x04\x05\x06\x07\x08"),
            '190102030405060708',
        ];
        yield 'bytes' => [new UnknownField(new Tag(4, WireType::BYTES), 'abc'), '2203616263'];
        yield 'empty bytes' => [new UnknownField(new Tag(4, WireType::BYTES), ''), '2200'];
        yield 'empty group' => [new UnknownField(new Tag(5, WireType::SGROUP), ''), '2b2c'];
        yield 'group with a nested group' => [
            new UnknownField(new Tag(5, WireType::SGROUP), (string) hex2bin('0801330a016134')),
            '2b0801330a0161342c',
        ];
        yield 'field number above one byte' => [new UnknownField(new Tag(300, WireType::VARINT), new Number(1)), 'e01201'];
    }

    public function testUnknownFieldsAreWrittenInTheOrderTheyAreHeld(): void
    {
        $bytes = new Serializer()->serialize(new Message([], [
            new UnknownField(new Tag(7, WireType::VARINT), new Number(1)),
            new UnknownField(new Tag(2, WireType::BYTES), 'a'),
            new UnknownField(new Tag(7, WireType::VARINT), new Number(2)),
        ]));

        self::assertSame('38011201613802', bin2hex($bytes));
    }

    public function testANestedMessageWritesItsUnknownFieldsInsideItsOwnBody(): void
    {
        $inner = new Message([fieldOf(1, int32Of(1))], [new UnknownField(new Tag(2, WireType::VARINT), new Number(2))]);
        $outer = new Message(
            [fieldOf(1, new Value($inner, messageT(fieldT(1, int32T))))],
            [new UnknownField(new Tag(3, WireType::VARINT), new Number(3))],
        );

        // field 1: {1: 1, unknown 2: 2}, then unknown 3: 3
        self::assertSame('0a04080110021803', bin2hex(new Serializer()->serialize($outer)));
    }

    /**
     * @param non-empty-string $hex
     */
    #[DataProvider('provideARoundTripIsByteExactWhenTheUnknownFieldsComeLastCases')]
    public function testARoundTripIsByteExactWhenTheUnknownFieldsComeLast(string $hex): void
    {
        $serializer = new Serializer();
        $type = messageT(
            fieldT(1, int32T),
            fieldT(2, enumT(UnknownFieldsSerializeTestColor::class)),
            fieldT(3, mapT(int32T, enumT(UnknownFieldsSerializeTestColor::class))),
            fieldT(9, messageT(fieldT(1, int32T))),
        );

        $message = $serializer->deserialize($type, (string) hex2bin($hex));

        self::assertSame($hex, bin2hex($serializer->serialize($message)));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideARoundTripIsByteExactWhenTheUnknownFieldsComeLastCases(): iterable
    {
        yield 'varint' => ['0805209601'];
        yield 'varint of a negative int64' => ['080520ffffffffffffffffff01'];
        yield 'fixed64' => ['0805290102030405060708'];
        yield 'bytes' => ['08053203616263'];
        yield 'empty bytes' => ['08053200'];
        yield 'group' => ['08053b08011102000000000000001a01611d030000003c'];
        yield 'nested groups' => ['08053b08014308011b1c443c'];
        yield 'fixed32' => ['08054501020304'];
        yield 'enum number without a case' => ['08051009'];
        // The entry of a map whose value is an enum number without a case is kept as it was written.
        yield 'map entry with an enum number without a case' => ['08051a0410090801'];
        yield 'unknown fields of a nested message' => ['08054a04080110022001'];
        yield 'every wire type' => [
            '0805'
            . '4a06080110021801'
            . '1009'
            . '1a0408011009'
            . '209601'
            . '290102030405060708'
            . '3203616263'
            . '3b08013c'
            . '4501020304',
        ];
    }

    public function testUnknownFieldsReadBeforeKnownOnesAreWrittenAfterThem(): void
    {
        $serializer = new Serializer();
        $message = $serializer->deserialize(messageT(fieldT(1, int32T)), (string) hex2bin('20010805'));

        self::assertSame('08052001', bin2hex($serializer->serialize($message)));
    }

    /**
     * @param non-empty-string $error
     */
    #[DataProvider('provideAnUnknownFieldWhoseValueDoesNotFitItsWireTypeIsRejectedCases')]
    public function testAnUnknownFieldWhoseValueDoesNotFitItsWireTypeIsRejected(UnknownField $unknown, string $error): void
    {
        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessage($error);

        new Serializer()->serialize(new Message([], [$unknown]));
    }

    /**
     * @return iterable<string, array{UnknownField, non-empty-string}>
     */
    public static function provideAnUnknownFieldWhoseValueDoesNotFitItsWireTypeIsRejectedCases(): iterable
    {
        yield 'end group tag' => [
            new UnknownField(new Tag(2, WireType::EGROUP), ''),
            'Unknown field 2 cannot be written: an end group tag',
        ];
        yield 'varint of bytes' => [
            new UnknownField(new Tag(2, WireType::VARINT), "\x01"),
            'Unknown field 2 cannot be written: a varint must hold a BcMath\Number.',
        ];
        yield 'bytes of a number' => [
            new UnknownField(new Tag(2, WireType::BYTES), new Number(1)),
            'Unknown field 2 cannot be written: a field of wire type BYTES must hold a string.',
        ];
        yield 'group of a number' => [
            new UnknownField(new Tag(2, WireType::SGROUP), new Number(1)),
            'Unknown field 2 cannot be written: a field of wire type SGROUP must hold a string.',
        ];
        yield 'fixed32 of 3 bytes' => [
            new UnknownField(new Tag(2, WireType::FIXED32), "\x01\x02\x03"),
            'Unknown field 2 cannot be written: a field of wire type FIXED32 must hold 4 bytes, got 3.',
        ];
        yield 'fixed64 of 4 bytes' => [
            new UnknownField(new Tag(2, WireType::FIXED64), "\x01\x02\x03\x04"),
            'Unknown field 2 cannot be written: a field of wire type FIXED64 must hold 8 bytes, got 4.',
        ];
    }

    /**
     * @param numeric-string $value
     */
    #[DataProvider('provideAVarintUnknownFieldOutOfRangeIsRejectedCases')]
    public function testAVarintUnknownFieldOutOfRangeIsRejected(string $value): void
    {
        $inner = new Message([], [new UnknownField(new Tag(2, WireType::VARINT), new Number($value))]);
        $outer = new Message([fieldOf(1, new Value($inner, messageT()))]);

        try {
            new Serializer()->serialize($outer);
            self::fail('The varint is out of range.');
        } catch (ValueOutOfRange $e) {
            self::assertSame([1, 2], $e->fields);
            self::assertSame('varint', $e->type);
        }
    }

    /**
     * @return iterable<string, array{numeric-string}>
     */
    public static function provideAVarintUnknownFieldOutOfRangeIsRejectedCases(): iterable
    {
        yield 'negative' => ['-1'];
        yield '2^64' => ['18446744073709551616'];
        yield 'fractional' => ['1.5'];
    }
}

/**
 * @internal
 */
enum UnknownFieldsSerializeTestColor: int
{
    case RED = 0;
    case GREEN = 1;
}
