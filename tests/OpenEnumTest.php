<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\DeserializeEnum;
use Thesis\Protobuf\Internal\Serde\UnknownEnumValue;

#[CoversClass(DeserializeEnum::class)]
#[CoversClass(UnknownEnumValue::class)]
#[CoversClass(Serializer::class)]
final class OpenEnumTest extends TestCase
{
    public function testKnownValueIsDecoded(): void
    {
        $decoded = self::decode(new OpenEnumTestWire(color: 2), OpenEnumTestMessage::class);

        self::assertSame(OpenEnumTestColor::BLUE, $decoded->color);
        self::assertSame([], UnknownFields::of($decoded));
    }

    public function testUnknownValueLeavesTheFieldUnsetAndKeepsTheNumber(): void
    {
        $decoded = self::decode(new OpenEnumTestWire(name: 'x', color: 68), OpenEnumTestMessage::class);

        self::assertSame('x', $decoded->name);
        self::assertSame(OpenEnumTestColor::UNSPECIFIED, $decoded->color);
        self::assertSame([[2, WireType::VARINT, '68']], self::unknowns($decoded));
    }

    public function testNegativeValues(): void
    {
        $known = self::decode(new OpenEnumTestWire(color: -1), OpenEnumTestMessage::class);
        self::assertSame(OpenEnumTestColor::NEGATIVE, $known->color);

        $unknown = self::decode(new OpenEnumTestWire(color: -5), OpenEnumTestMessage::class);
        self::assertSame(OpenEnumTestColor::UNSPECIFIED, $unknown->color);
        self::assertSame([[2, WireType::VARINT, '18446744073709551611']], self::unknowns($unknown), 'The raw 64-bit varint is kept.');
    }

    public function testPackedRepeatedKeepsKnownValuesInOrder(): void
    {
        $decoded = self::decode(new OpenEnumTestWire(packed: [1, 68, 2, 69]), OpenEnumTestMessage::class);

        self::assertSame([OpenEnumTestColor::RED, OpenEnumTestColor::BLUE], $decoded->packed);
        self::assertSame([[3, WireType::VARINT, '68'], [3, WireType::VARINT, '69']], self::unknowns($decoded));
    }

    public function testUnpackedRepeatedKeepsKnownValuesInOrder(): void
    {
        $decoded = self::decode(new OpenEnumTestWire(unpacked: [1, 68, 2, 69]), OpenEnumTestMessage::class);

        self::assertSame([OpenEnumTestColor::RED, OpenEnumTestColor::BLUE], $decoded->unpacked);
        self::assertSame([[4, WireType::VARINT, '68'], [4, WireType::VARINT, '69']], self::unknowns($decoded));
    }

    public function testOneofWithAnUnknownEnumVariantIsUnset(): void
    {
        $decoded = self::decode(new OpenEnumTestWire(choice: 68), OpenEnumTestMessage::class);

        self::assertNull($decoded->choice);
        self::assertSame([[5, WireType::VARINT, '68']], self::unknowns($decoded));
    }

    public function testNestedMessages(): void
    {
        $decoded = self::decode(
            new OpenEnumTestWireParent([new OpenEnumTestWire(color: 1), new OpenEnumTestWire(color: 68)]),
            OpenEnumTestParent::class,
        );

        self::assertSame(
            [OpenEnumTestColor::RED, OpenEnumTestColor::UNSPECIFIED],
            array_map(static fn(OpenEnumTestMessage $child): OpenEnumTestColor => $child->color, $decoded->children),
        );
        self::assertSame([[], [[2, WireType::VARINT, '68']]], array_map(self::unknowns(...), $decoded->children));
    }

    public function testMapEntriesWithUnknownValuesAreDropped(): void
    {
        $decoded = self::decode(
            new OpenEnumTestWire(map: new Map(new KVPair('known', 1), new KVPair('unknown', 68))),
            OpenEnumTestMessage::class,
        );

        self::assertEquals(new Map(new KVPair('known', OpenEnumTestColor::RED)), $decoded->map);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private static function decode(object $wire, string $class): object
    {
        $bytes = Encoder\Builder::buildDefault()->encode($wire);

        return new Decoder\Builder()->withUnknownHandler(UnknownFields::handler())->build()->decode($bytes, $class);
    }

    /**
     * @return list<array{int, WireType, string}>
     */
    private static function unknowns(object $message): array
    {
        return array_map(
            static fn(UnknownFields\UnknownField $field): array => [$field->tag->num, $field->tag->type, (string) $field->value],
            UnknownFields::of($message),
        );
    }
}

enum OpenEnumTestColor: int
{
    case NEGATIVE = -1;
    case UNSPECIFIED = 0;
    case RED = 1;
    case BLUE = 2;
}

/**
 * The decoding side: enum fields.
 */
final readonly class OpenEnumTestMessage
{
    /**
     * @param list<OpenEnumTestColor> $packed
     * @param list<OpenEnumTestColor> $unpacked
     * @param Map<string, OpenEnumTestColor> $map
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, new Reflection\EnumT(OpenEnumTestColor::class))]
        public OpenEnumTestColor $color = OpenEnumTestColor::UNSPECIFIED,
        #[Reflection\Field(3, new Reflection\ListT(new Reflection\EnumT(OpenEnumTestColor::class), packed: true))]
        public array $packed = [],
        #[Reflection\Field(4, new Reflection\ListT(new Reflection\EnumT(OpenEnumTestColor::class), packed: false))]
        public array $unpacked = [],
        #[Reflection\OneOf([OpenEnumTestChoiceColor::class, OpenEnumTestChoiceName::class])]
        public ?OpenEnumTestChoice $choice = null,
        #[Reflection\Field(7, new Reflection\MapT(Reflection\StringT::T, new Reflection\EnumT(OpenEnumTestColor::class)))]
        public Map $map = new Map(),
    ) {}
}

interface OpenEnumTestChoice {}

final readonly class OpenEnumTestChoiceColor implements OpenEnumTestChoice
{
    public function __construct(
        #[Reflection\Field(5, new Reflection\EnumT(OpenEnumTestColor::class))]
        public OpenEnumTestColor $color = OpenEnumTestColor::UNSPECIFIED,
    ) {}
}

final readonly class OpenEnumTestChoiceName implements OpenEnumTestChoice
{
    public function __construct(
        #[Reflection\Field(6, Reflection\StringT::T)]
        public string $name = '',
    ) {}
}

/**
 * The encoding side: the same field numbers as plain int32, so any number can be written.
 */
final readonly class OpenEnumTestWire
{
    /**
     * @param list<int> $packed
     * @param list<int> $unpacked
     * @param Map<string, int> $map
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $color = 0,
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Int32T::T, packed: true))]
        public array $packed = [],
        #[Reflection\Field(4, new Reflection\ListT(Reflection\Int32T::T, packed: false))]
        public array $unpacked = [],
        #[Reflection\Field(5, Reflection\Int32T::T)]
        public int $choice = 0,
        #[Reflection\Field(7, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public Map $map = new Map(),
    ) {}
}

final readonly class OpenEnumTestParent
{
    /**
     * @param list<OpenEnumTestMessage> $children
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(OpenEnumTestMessage::class)))]
        public array $children = [],
    ) {}
}

final readonly class OpenEnumTestWireParent
{
    /**
     * @param list<OpenEnumTestWire> $children
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(OpenEnumTestWire::class)))]
        public array $children = [],
    ) {}
}
