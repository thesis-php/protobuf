<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\ListCodec;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\SingularCodec;

#[CoversClass(Serializer::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(SingularCodec::class)]
#[CoversClass(ListCodec::class)]
#[CoversClass(MapCodec::class)]
final class WireTypeMismatchTest extends TestCase
{
    /**
     * @param Type<mixed, *, *, *> $type
     */
    #[DataProvider('provideAFieldWithAnUnexpectedWireTypeIsAnUnknownFieldCases')]
    public function testAFieldWithAnUnexpectedWireTypeIsAnUnknownField(
        Type $type,
        string $hex,
        WireType $wireType,
        string $unknown,
    ): void {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, $type), fieldT(2, int32T)),
            (string) hex2bin($hex . '1007'),
        );

        self::assertArrayNotHasKey(1, $message->fields);
        self::assertSame(7, ($message->fields[2] ?? null)?->value->value);
        self::assertCount(1, $message->unknowns);
        self::assertTrue($message->unknowns[0]->tag->equal(new Tag(1, $wireType)));
        self::assertSame($unknown, (string) $message->unknowns[0]->value);
    }

    /**
     * @return iterable<string, array{Type<mixed, *, *, *>, string, WireType, string}>
     */
    public static function provideAFieldWithAnUnexpectedWireTypeIsAnUnknownFieldCases(): iterable
    {
        yield 'int32 as bytes' => [int32T, '0a03616263', WireType::BYTES, 'abc'];
        yield 'int64 as fixed64' => [int64T, '090100000000000000', WireType::FIXED64, "\x01\0\0\0\0\0\0\0"];
        yield 'bool as fixed32' => [boolT, '0d01000000', WireType::FIXED32, "\x01\0\0\0"];
        yield 'enum as bytes' => [enumT(WireTypeMismatchTestColor::class), '0a0101', WireType::BYTES, "\x01"];
        yield 'string as varint' => [stringT, '0802', WireType::VARINT, '2'];
        yield 'message as varint' => [messageT(fieldT(1, int32T)), '0802', WireType::VARINT, '2'];
        yield 'float as fixed64' => [floatT, '090000000000000000', WireType::FIXED64, "\0\0\0\0\0\0\0\0"];
        yield 'double as fixed32' => [doubleT, '0d00000000', WireType::FIXED32, "\0\0\0\0"];
        yield 'fixed32 as varint' => [fixed32T, '0805', WireType::VARINT, '5'];
        yield 'sfixed64 as varint' => [sfixed64T, '0805', WireType::VARINT, '5'];
        yield 'repeated int32 as fixed32' => [listT(int32T), '0d01000000', WireType::FIXED32, "\x01\0\0\0"];
        yield 'repeated fixed32 as varint' => [listT(fixed32T), '0805', WireType::VARINT, '5'];
        yield 'repeated string as varint' => [listT(stringT), '0802', WireType::VARINT, '2'];
        yield 'repeated message as fixed32' => [listT(messageT(fieldT(1, int32T))), '0d01000000', WireType::FIXED32, "\x01\0\0\0"];
        yield 'map as varint' => [mapT(stringT, int32T), '0802', WireType::VARINT, '2'];
    }

    public function testAnOccurrenceWithAnUnexpectedWireTypeKeepsThePreviousValue(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, int32T)),
            (string) hex2bin('08050a0161'),
        );

        self::assertSame(5, ($message->fields[1] ?? null)?->value->value);
        self::assertCount(1, $message->unknowns);
    }

    public function testElementsWithAnUnexpectedWireTypeAreSkippedAmongTheOthers(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, listT(int32T))),
            (string) hex2bin('08010d0200000008030a0104'),
        );

        self::assertSame([1, 3, 4], ($message->fields[1] ?? null)?->value->value);
        self::assertCount(1, $message->unknowns);
    }

    /**
     * @param Type\ListT<*> $type
     * @param list<mixed> $expected
     */
    #[DataProvider('provideBothPackedAndUnpackedElementsAreAcceptedCases')]
    public function testBothPackedAndUnpackedElementsAreAccepted(Type\ListT $type, string $hex, array $expected): void
    {
        $message = new Serializer()->deserialize(messageT(fieldT(1, $type)), (string) hex2bin($hex));

        self::assertSame($expected, ($message->fields[1] ?? null)?->value->value);
        self::assertSame([], $message->unknowns);
    }

    /**
     * @return iterable<string, array{Type\ListT<*>, string, list<mixed>}>
     */
    public static function provideBothPackedAndUnpackedElementsAreAcceptedCases(): iterable
    {
        yield 'packed int32 declared unpacked' => [listT(int32T, packed: false), '0a020102', [1, 2]];
        yield 'unpacked int32 declared packed' => [listT(int32T, packed: true), '08010802', [1, 2]];
        yield 'packed fixed32' => [listT(fixed32T), '0a080100000002000000', [1, 2]];
        yield 'unpacked fixed32' => [listT(fixed32T), '0d010000000d02000000', [1, 2]];
        yield 'packed double' => [listT(doubleT), '0a08000000000000f03f', [1.0]];
        yield 'unpacked double' => [listT(doubleT), '09000000000000f03f', [1.0]];
        yield 'packed enum' => [listT(enumT(WireTypeMismatchTestColor::class)), '0a0101', [WireTypeMismatchTestColor::RED]];
        yield 'strings' => [listT(stringT), '0a01610a0162', ['a', 'b']];
    }

    public function testAMapEntryFieldWithAnUnexpectedWireTypeIsSkipped(): void
    {
        // The entry holds key 1 as varint, then key "a" and value 5 as declared, then value 2 as bytes.
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, int32T))),
            (string) hex2bin('0a0c08010a016110051203616263'),
        );

        $map = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 5], iterator_to_array($map));
        self::assertSame([], $message->unknowns);
    }

    public function testDecoderSkipsAFieldWithAnUnexpectedWireType(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('08021007'),
            WireTypeMismatchTestMessage::class,
        );

        self::assertSame('', $decoded->name);
        self::assertSame(7, $decoded->id);
    }
}

enum WireTypeMismatchTestColor: int
{
    case RED = 1;
}

final readonly class WireTypeMismatchTestMessage
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
    ) {}
}
