<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\ListCodec;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;

#[CoversClass(Serializer::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(ListCodec::class)]
#[CoversClass(MapCodec::class)]
final class RepeatedFieldMergeTest extends TestCase
{
    /**
     * @param Type\ListT<*> $type
     * @param list<mixed> $expected
     */
    #[DataProvider('provideOccurrencesOfARepeatedFieldAreConcatenatedCases')]
    public function testOccurrencesOfARepeatedFieldAreConcatenated(Type\ListT $type, string $hex, array $expected): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, $type), fieldT(2, int32T)),
            (string) hex2bin($hex),
        );

        self::assertSame($expected, ($message->fields[1] ?? null)?->value->value);
    }

    /**
     * @return iterable<string, array{Type\ListT<*>, string, list<mixed>}>
     */
    public static function provideOccurrencesOfARepeatedFieldAreConcatenatedCases(): iterable
    {
        yield 'strings interleaved with another field' => [listT(stringT), '0a016110070a0162', ['a', 'b']];
        yield 'two packed chunks' => [listT(int32T), '0a0201020a0103', [1, 2, 3]];
        yield 'unpacked, then packed' => [listT(int32T), '08010a0103', [1, 3]];
        yield 'packed, then unpacked' => [listT(int32T), '0a0201020803', [1, 2, 3]];
        yield 'packed chunks interleaved with another field' => [listT(int32T), '0a010110070a0102', [1, 2]];
        yield 'unpacked elements interleaved with another field' => [listT(int32T), '0801100708020803', [1, 2, 3]];
    }

    public function testOccurrencesOfAMapFieldAreMerged(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, int32T)), fieldT(2, int32T)),
            (string) hex2bin('0a050a0161100110070a050a01621002'),
        );

        $map = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 1, 'b' => 2], iterator_to_array($map));
    }

    public function testTheLastEntryOfARepeatedMapKeyWins(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, int32T))),
            (string) hex2bin('0a050a016110010a050a016210020a050a01611003'),
        );

        $map = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 3, 'b' => 2], iterator_to_array($map));
    }

    public function testTheLastOccurrenceOfAScalarWins(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, int32T), fieldT(2, stringT)),
            (string) hex2bin('08011201610802'),
        );

        self::assertSame(2, ($message->fields[1] ?? null)?->value->value);
        self::assertSame('a', ($message->fields[2] ?? null)?->value->value);
    }

    public function testRepeatedFieldsOfNestedMessagesAreConcatenated(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, messageT(fieldT(1, listT(int32T)), fieldT(2, int32T)))),
            (string) hex2bin('0a06080110070802'),
        );

        $inner = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Message::class, $inner);
        self::assertSame([1, 2], ($inner->fields[1] ?? null)?->value->value);
    }

    public function testUnknownEnumValuesOfEveryOccurrenceAreKept(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, listT(enumT(RepeatedFieldMergeTestColor::class))), fieldT(2, int32T)),
            (string) hex2bin('0801080910070a020902'),
        );

        self::assertSame(
            [RepeatedFieldMergeTestColor::RED, RepeatedFieldMergeTestColor::BLUE],
            ($message->fields[1] ?? null)?->value->value,
        );
        self::assertSame(['9', '9'], array_map(
            static fn(UnknownFields\UnknownField $field): string => (string) $field->value,
            $message->unknowns,
        ));
    }

    public function testFieldsComeInTheOrderTheyFirstOccur(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, int32T), fieldT(2, int32T)),
            (string) hex2bin('100208011003'),
        );

        self::assertSame([2, 1], array_keys($message->fields));
        self::assertSame(3, ($message->fields[2] ?? null)?->value->value);
    }

    public function testDecoderConcatenatesOccurrencesOfARepeatedField(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0a016110070a0162'),
            RepeatedFieldMergeTestMessage::class,
        );

        self::assertSame(['a', 'b'], $decoded->names);
        self::assertSame(7, $decoded->id);
    }
}

enum RepeatedFieldMergeTestColor: int
{
    case RED = 1;
    case BLUE = 2;
}

final readonly class RepeatedFieldMergeTestMessage
{
    /**
     * @param list<string> $names
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\StringT::T))]
        public array $names = [],
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
    ) {}
}
