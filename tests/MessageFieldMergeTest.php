<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\DeserializeMessage;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\SingularCodec;

#[CoversClass(Serializer::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(SingularCodec::class)]
#[CoversClass(DeserializeMessage::class)]
final class MessageFieldMergeTest extends TestCase
{
    public function testOccurrencesOfAMessageFieldAreMerged(): void
    {
        $inner = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, int32T), fieldT(2, int32T)))),
            '0a0208010a021002',
        );

        self::assertSame([1 => 1, 2 => 2], self::values($inner));
    }

    public function testOccurrencesInterleavedWithAnotherFieldAreMerged(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, messageT(fieldT(1, int32T), fieldT(2, int32T))), fieldT(2, int32T)),
            (string) hex2bin('0a02080110070a021002'),
        );

        $inner = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Message::class, $inner);
        self::assertSame([1 => 1, 2 => 2], self::values($inner));
        self::assertSame(7, ($message->fields[2] ?? null)?->value->value);
    }

    public function testTheScalarOfTheLaterOccurrenceWins(): void
    {
        $inner = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, int32T), fieldT(2, stringT)))),
            '0a0508011201610a020803',
        );

        self::assertSame([1 => 3, 2 => 'a'], self::values($inner));
    }

    public function testRepeatedFieldsOfTheOccurrencesAreConcatenated(): void
    {
        $inner = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, listT(int32T))))),
            '0a030a01010a040a020203',
        );

        self::assertSame([1 => [1, 2, 3]], self::values($inner));
    }

    public function testMapEntriesOfTheOccurrencesAreAdded(): void
    {
        $inner = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, mapT(stringT, int32T))))),
            '0a070a050a016110010a070a050a01621002',
        );

        $map = ($inner->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Map::class, $map);
        self::assertSame(['a' => 1, 'b' => 2], iterator_to_array($map));
    }

    public function testNestedMessagesAreMergedRecursively(): void
    {
        $middle = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, messageT(fieldT(1, int32T), fieldT(2, int32T))), fieldT(2, int32T)))),
            '0a040a0208010a0210050a040a021002',
        );

        $inner = ($middle->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Message::class, $inner);
        self::assertSame([1 => 1, 2 => 2], self::values($inner));
        self::assertSame(5, ($middle->fields[2] ?? null)?->value->value);
    }

    public function testFieldsOfTheMergedMessageComeInTheOrderTheyFirstOccur(): void
    {
        $inner = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, int32T), fieldT(2, int32T), fieldT(3, int32T)))),
            '0a0210020a0418030801',
        );

        self::assertSame([2, 3, 1], array_keys($inner->fields));
    }

    public function testUnknownFieldsOfEveryOccurrenceAreKept(): void
    {
        $inner = self::inner(
            messageT(fieldT(1, messageT(fieldT(1, int32T)))),
            '0a04180508010a021806',
        );

        self::assertSame([1 => 1], self::values($inner));
        self::assertSame(['5', '6'], array_map(
            static fn(UnknownFields\UnknownField $field): string => (string) $field->value,
            $inner->unknowns,
        ));
    }

    public function testElementsOfARepeatedMessageFieldAreNotMerged(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, listT(messageT(fieldT(1, int32T), fieldT(2, int32T))))),
            (string) hex2bin('0a0208010a021002'),
        );

        $elements = ($message->fields[1] ?? null)?->value->value;

        self::assertIsArray($elements);
        self::assertContainsOnlyInstancesOf(Message::class, $elements);
        self::assertSame([[1 => 1], [2 => 2]], array_map(self::values(...), $elements));
    }

    public function testMapValuesOfARepeatedKeyAreNotMerged(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, messageT(fieldT(1, int32T), fieldT(2, int32T))))),
            (string) hex2bin('0a070a0161120208010a070a016112021002'),
        );

        $map = ($message->fields[1] ?? null)?->value->value;

        self::assertInstanceOf(Map::class, $map);
        $value = $map['a'];
        self::assertInstanceOf(Message::class, $value);
        self::assertSame([2 => 2], self::values($value));
    }

    public function testDecoderMergesOccurrencesOfANestedObject(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0a02080110070a021002'),
            MessageFieldMergeTestOuter::class,
        );

        self::assertEquals(new MessageFieldMergeTestInner(1, 2), $decoded->inner);
        self::assertSame(7, $decoded->id);
    }

    public function testDecoderMergesOccurrencesOfAOneofVariantObject(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('1a0208011a021002'),
            MessageFieldMergeTestOuter::class,
        );

        self::assertEquals(
            new MessageFieldMergeTestChoiceInner(new MessageFieldMergeTestInner(1, 2)),
            $decoded->choice,
        );
    }

    private static function inner(Type\MessageT $type, string $hex): Message
    {
        $message = new Serializer()->deserialize($type, (string) hex2bin($hex));

        $inner = ($message->fields[1] ?? null)?->value->value;
        self::assertInstanceOf(Message::class, $inner);

        return $inner;
    }

    /**
     * @return array<positive-int, mixed>
     */
    private static function values(Message $message): array
    {
        return array_map(
            static fn(FieldDescriptor $field): mixed => $field->value->value,
            $message->fields,
        );
    }
}

final readonly class MessageFieldMergeTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $a = 0,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $b = 0,
    ) {}
}

final readonly class MessageFieldMergeTestOuter
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(MessageFieldMergeTestInner::class))]
        public ?MessageFieldMergeTestInner $inner = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([MessageFieldMergeTestChoiceInner::class, MessageFieldMergeTestChoiceName::class])]
        public ?MessageFieldMergeTestChoice $choice = null,
    ) {}
}

interface MessageFieldMergeTestChoice {}

final readonly class MessageFieldMergeTestChoiceInner implements MessageFieldMergeTestChoice
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\ObjectT(MessageFieldMergeTestInner::class))]
        public ?MessageFieldMergeTestInner $inner = null,
    ) {}
}

final readonly class MessageFieldMergeTestChoiceName implements MessageFieldMergeTestChoice
{
    public function __construct(
        #[Reflection\Field(4, Reflection\StringT::T)]
        public string $name = '',
    ) {}
}
