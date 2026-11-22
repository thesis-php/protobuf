<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\MalformedGroup;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Exception\UnsupportedWireType;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;

#[CoversClass(MessageFields::class)]
#[CoversClass(MapCodec::class)]
#[CoversClass(MalformedGroup::class)]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\discardUnknown')]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\readGroup')]
final class UnknownGroupTest extends TestCase
{
    /**
     * @param non-empty-string $group the hex of the group, from its start group tag of field 3 to its end group tag
     */
    #[DataProvider('provideAGroupIsSkippedAsAnUnknownFieldCases')]
    public function testAGroupIsSkippedAsAnUnknownField(string $group, string $fields): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, int32T), fieldT(2, int32T)),
            (string) hex2bin('0805' . $group . '1007'),
        );

        self::assertSame(5, ($message->fields[1] ?? null)?->value->value);
        self::assertSame(7, ($message->fields[2] ?? null)?->value->value);
        self::assertCount(1, $message->unknowns);
        self::assertTrue($message->unknowns[0]->tag->equal(new Tag(3, WireType::SGROUP)));
        self::assertSame((string) hex2bin($fields), $message->unknowns[0]->value);
    }

    /**
     * @return iterable<string, array{non-empty-string, string}>
     */
    public static function provideAGroupIsSkippedAsAnUnknownFieldCases(): iterable
    {
        yield 'empty' => ['1b1c', ''];
        // Field 1 inside the group is a field of the group, not of the message.
        yield 'with a declared field number' => ['1b08011c', '0801'];
        // A varint, a fixed64, bytes and a fixed32.
        yield 'with every wire type' => [
            '1b08011102000000000000001a01611d030000001c',
            '08011102000000000000001a01611d03000000',
        ];
        yield 'with a nested group' => ['1b0801230a0161241c', '0801230a016124'];
        yield 'with a nested group of the same field' => ['1b1b08011c1c', '1b08011c'];
        // The end group byte inside a length-delimited value is a part of the value, not a tag.
        yield 'with an end group byte inside bytes' => ['1b0a011c1c', '0a011c'];
    }

    public function testADeclaredFieldWrittenAsAGroupIsAnUnknownField(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, messageT(fieldT(1, int32T))), fieldT(2, int32T)),
            (string) hex2bin('0b08010c1007'),
        );

        self::assertArrayNotHasKey(1, $message->fields);
        self::assertSame(7, ($message->fields[2] ?? null)?->value->value);
        self::assertCount(1, $message->unknowns);
        self::assertTrue($message->unknowns[0]->tag->equal(new Tag(1, WireType::SGROUP)));
        self::assertSame("\x08\x01", $message->unknowns[0]->value);
    }

    public function testAGroupInANestedMessageIsSkipped(): void
    {
        // The nested message holds an empty group of field 3, then field 1.
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, messageT(fieldT(1, int32T)))),
            (string) hex2bin('0a041b1c0801'),
        );

        $nested = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Message::class, $nested);
        self::assertSame(1, ($nested->fields[1] ?? null)?->value->value);
        self::assertCount(1, $nested->unknowns);
        self::assertTrue($nested->unknowns[0]->tag->equal(new Tag(3, WireType::SGROUP)));
        self::assertSame([], $message->unknowns);
    }

    public function testAGroupInAMapEntryIsSkipped(): void
    {
        // The entry holds key "a", a group of field 3, then value 5.
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, int32T))),
            (string) hex2bin('0a090a01611b08011c1005'),
        );

        $map = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 5], iterator_to_array($map));
        self::assertSame([], $message->unknowns);
    }

    public function testDeeplyNestedGroupsAreSkipped(): void
    {
        $depth = 100_000;

        $message = new Serializer()->deserialize(
            messageT(fieldT(2, int32T)),
            str_repeat("\x1b", $depth) . str_repeat("\x1c", $depth) . "\x10\x07",
        );

        self::assertSame(7, ($message->fields[2] ?? null)?->value->value);
        self::assertCount(1, $message->unknowns);
        self::assertSame(2 * ($depth - 1), \strlen((string) $message->unknowns[0]->value));
    }

    public function testDecoderSkipsAGroup(): void
    {
        $decoded = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::handler())
            ->build()
            ->decode((string) hex2bin('1b08011c1007'), UnknownGroupTestMessage::class);

        self::assertSame(7, $decoded->id);

        $unknowns = UnknownFields::of($decoded);
        self::assertCount(1, $unknowns);
        self::assertTrue($unknowns[0]->tag->equal(new Tag(3, WireType::SGROUP)));
        self::assertSame("\x08\x01", $unknowns[0]->value);
    }

    /**
     * @param non-empty-string $hex
     * @param class-string<MalformedMessage> $exception
     */
    #[DataProvider('provideAMalformedGroupIsReportedCases')]
    public function testAMalformedGroupIsReported(string $hex, string $exception, string $message): void
    {
        try {
            new Serializer()->deserialize(
                messageT(fieldT(1, int32T), fieldT(2, messageT(fieldT(1, int32T)))),
                (string) hex2bin($hex),
            );
            self::fail('Deserialization must fail.');
        } catch (MalformedMessage $e) {
            self::assertInstanceOf($exception, $e);
            self::assertStringContainsString($message, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{non-empty-string, class-string<MalformedMessage>, string}>
     */
    public static function provideAMalformedGroupIsReportedCases(): iterable
    {
        yield 'end group without a group' => ['1c', MalformedGroup::class, 'end group tag of field 3: no group is open'];
        yield 'end group after a field' => ['08011c', MalformedGroup::class, 'no group is open'];
        yield 'end group of another field' => ['1b24', MalformedGroup::class, 'end group tag of field 4: the group of field 3 is open'];
        yield 'end group of the outer group first' => ['1b231c24', MalformedGroup::class, 'the group of field 4 is open'];
        yield 'group not closed' => ['1b0801', BufferUnderflow::class, 'the group of field 3 is not closed'];
        yield 'nested group not closed' => ['1b23241b', BufferUnderflow::class, 'not closed'];
        yield 'group not closed within a nested message' => ['12011b1c', BufferUnderflow::class, 'not closed'];
        yield 'truncated value inside a group' => ['1b08', BufferUnderflow::class, 'truncated varint'];
        yield 'bytes beyond the end inside a group' => ['1b0a05611c', BufferUnderflow::class, "Expected '5' bytes"];
        yield 'wire type 7 inside a group' => ['1b0f1c', UnsupportedWireType::class, 'wire type 7'];
    }
}

final readonly class UnknownGroupTestMessage
{
    public function __construct(
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
    ) {}
}
