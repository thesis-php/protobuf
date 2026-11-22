<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(ClassReflector::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(ToProtobufValueTypeVisitor::class)]
#[CoversClass(ToValueTypeVisitor::class)]
#[CoversClass(IsValueEmpty::class)]
#[CoversClass(MapCodec::class)]
final class MapArrayPropertyTest extends TestCase
{
    public function testAnArrayIsEncodedAsTheSameMap(): void
    {
        $array = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestStrings(['a' => 1, 'b' => 2]));
        $map = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestMap(Map::fromArray(['a' => 1, 'b' => 2])));

        self::assertSame('0a050a016110010a050a01621002', bin2hex($array));
        self::assertSame($map, $array);
    }

    public function testAnArrayIsDecodedAsAnArray(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0a050a016110010a050a01621002'),
            MapArrayPropertyTestStrings::class,
        );

        self::assertSame(['a' => 1, 'b' => 2], $decoded->values);
    }

    /**
     * @param class-string $class
     * @param 'array'|'map' $expected
     */
    #[DataProvider('provideThePropertyTypeTellsWhatAMapIsDecodedAsCases')]
    public function testThePropertyTypeTellsWhatAMapIsDecodedAs(string $class, string $expected): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode((string) hex2bin('0a050a01611001'), $class);
        $values = new \ReflectionProperty($class, 'values')->getValue($decoded);

        if ($expected === 'array') {
            self::assertSame(['a' => 1], $values);
        } else {
            self::assertInstanceOf(Map::class, $values);
            self::assertSame(['a' => 1], iterator_to_array($values));
        }
    }

    /**
     * @return iterable<string, array{class-string, 'array'|'map'}>
     */
    public static function provideThePropertyTypeTellsWhatAMapIsDecodedAsCases(): iterable
    {
        yield 'array' => [MapArrayPropertyTestStrings::class, 'array'];
        yield 'nullable array' => [MapArrayPropertyTestNullable::class, 'array'];
        yield 'array or string' => [MapArrayPropertyTestArrayOrString::class, 'array'];
        yield 'Map' => [MapArrayPropertyTestMap::class, 'map'];
        yield 'array or Map' => [MapArrayPropertyTestArrayOrMap::class, 'map'];
        yield 'iterable' => [MapArrayPropertyTestIterable::class, 'map'];
        yield 'interface of Map' => [MapArrayPropertyTestCountable::class, 'map'];
        yield 'mixed' => [MapArrayPropertyTestMixed::class, 'map'];
        yield 'untyped' => [MapArrayPropertyTestUntyped::class, 'map'];
    }

    /**
     * @param class-string $class
     * @param non-empty-string $key
     */
    #[DataProvider('provideAnArrayCannotHoldKeysOtherThanIntsAndStringsCases')]
    public function testAnArrayCannotHoldKeysOtherThanIntsAndStrings(string $class, string $key): void
    {
        $this->expectExceptionObject(new InvalidProperty($class, 'values', \sprintf(
            'of type %s cannot hold the keys of the protobuf map field 1: an array key is an int or a string, not %s; declare the property as %s',
            (string) new \ReflectionProperty($class, 'values')->getType(),
            $key,
            Map::class,
        )));

        new ClassReflector()->reflect($class);
    }

    /**
     * @return iterable<string, array{class-string, non-empty-string}>
     */
    public static function provideAnArrayCannotHoldKeysOtherThanIntsAndStringsCases(): iterable
    {
        yield 'bool' => [MapArrayPropertyTestBoolKeys::class, 'bool'];
        yield 'uint64' => [MapArrayPropertyTestUint64Keys::class, \BcMath\Number::class];
        yield 'fixed64' => [MapArrayPropertyTestFixed64Keys::class, \BcMath\Number::class];
        yield 'nullable array for bool' => [MapArrayPropertyTestNullableBoolKeys::class, 'bool'];
    }

    public function testAPropertyThatHoldsAMapAcceptsAnyKeys(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = Decoder\Builder::buildDefault();

        $bool = $decoder->decode(
            $encoder->encode(new MapArrayPropertyTestBoolKeysOrMap(new Map(new KVPair(true, 1)))),
            MapArrayPropertyTestBoolKeysOrMap::class,
        );

        self::assertInstanceOf(Map::class, $bool->values);
        self::assertSame(1, $bool->values[true]);

        $iterable = $decoder->decode(
            $encoder->encode(new MapArrayPropertyTestUint64KeysIterable(new Map(new KVPair(new \BcMath\Number('18446744073709551615'), 1)))),
            MapArrayPropertyTestUint64KeysIterable::class,
        );

        self::assertInstanceOf(Map::class, $iterable->values);
        self::assertSame(1, $iterable->values[new \BcMath\Number('18446744073709551615')]);
    }

    public function testStringKeysThatLookLikeNumbersRoundTrip(): void
    {
        $values = [
            '1' => 1,
            '-1' => 2,
            '01' => 3,
            '1.5' => 4,
            '-0' => 5,
            ' 1' => 6,
            '9223372036854775807' => 7,
            '9223372036854775808' => 8,
            '99999999999999999999' => 9,
            'a' => 10,
        ];

        // PHP turns the decimal integer keys into ints, "01", "1.5", "-0", " 1" and the keys beyond PHP_INT_MAX stay
        // strings; every key is written as the string it was written as.
        self::assertSame(
            [1, -1, '01', '1.5', '-0', ' 1', PHP_INT_MAX, '9223372036854775808', '99999999999999999999', 'a'],
            array_keys($values),
        );

        $bytes = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestStrings($values));

        $strings = new Map();

        foreach ($values as $key => $value) {
            $strings[(string) $key] = $value;
        }

        self::assertSame(
            bin2hex(new Serializer()->serialize(message(fieldOf(1, mapOf(stringT, int32T, $strings))))),
            bin2hex($bytes),
        );

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestStrings::class);

        self::assertSame($values, $decoded->values);
    }

    public function testANumericStringKeyIsDecodedAsAnIntKey(): void
    {
        // The entries "1" => 5 and "01" => 6, as another implementation writes them.
        $bytes = new Serializer()->serialize(message(fieldOf(1, mapOf(stringT, int32T, new Map(
            new KVPair('1', 5),
            new KVPair('01', 6),
        )))));

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestStrings::class);

        self::assertSame([1 => 5, '01' => 6], $decoded->values);
    }

    public function testInt64KeysRoundTrip(): void
    {
        $values = [PHP_INT_MIN => 1, -1 => 2, 0 => 3, PHP_INT_MAX => 4];

        $bytes = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestInt64Keys($values));
        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestInt64Keys::class);

        self::assertSame($values, $decoded->values);
    }

    public function testAnIntKeyOutOfTheRangeOfTheKeyTypeIsRejected(): void
    {
        try {
            Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestInt32Keys([1 => 1, 2 ** 31 => 2]));
            self::fail('The key 2^31 is not an int32.');
        } catch (ValueOutOfRange $e) {
            self::assertSame('int32', $e->type);
            self::assertSame(2 ** 31, $e->value);
            self::assertSame([1, 1], $e->fields);
        }

        try {
            Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestUint32Keys([-1 => 1]));
            self::fail('The key -1 is not a uint32.');
        } catch (ValueOutOfRange $e) {
            self::assertSame('uint32', $e->type);
            self::assertSame([1, 1], $e->fields);
        }
    }

    public function testMessageValuesRoundTrip(): void
    {
        $inner = new MapArrayPropertyTestInner(5);
        $object = new MapArrayPropertyTestMessages([7 => $inner, 9 => new MapArrayPropertyTestInner(0)]);

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertSame(
            Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestMessagesMap(Map::fromArray($object->values))),
            $bytes,
        );
        self::assertSame($inner, $object->values[7] ?? null);

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestMessages::class);

        self::assertEquals($object->values, $decoded->values);
        self::assertSame([7, 9], array_keys($decoded->values));
    }

    public function testEnumValuesRoundTrip(): void
    {
        $values = ['zero' => MapArrayPropertyTestKind::Zero, 'one' => MapArrayPropertyTestKind::One];

        $bytes = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestEnums($values));
        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestEnums::class);

        self::assertSame($values, $decoded->values);
    }

    public function testAnArrayInANestedObjectRoundTrips(): void
    {
        $object = new MapArrayPropertyTestOuter(new MapArrayPropertyTestStrings(['1' => 1, 'x' => 2]));

        $bytes = Encoder\Builder::buildDefault()->encode($object);
        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestOuter::class);

        self::assertEquals($object, $decoded);
        self::assertSame([1 => 1, 'x' => 2], $decoded->inner?->values);
    }

    public function testAnArrayInAOneOfVariantRoundTrips(): void
    {
        $object = new MapArrayPropertyTestChoice(new MapArrayPropertyTestVariant([3 => 'c']));

        $bytes = Encoder\Builder::buildDefault()->encode($object);
        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestChoice::class);

        self::assertEquals($object, $decoded);
    }

    public function testAnEmptyArrayIsNotWrittenAndAMissingFieldIsTheDefault(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestStrings());

        self::assertSame('', $bytes);
        self::assertSame([], Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestStrings::class)->values);
    }

    public function testANullableArrayKeepsNull(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestNullable());

        self::assertSame('', $bytes);
        self::assertNull(Decoder\Builder::buildDefault()->decode($bytes, MapArrayPropertyTestNullable::class)->values);

        $decoded = Decoder\Builder::buildDefault()->decode(
            Encoder\Builder::buildDefault()->encode(new MapArrayPropertyTestNullable(['a' => 0])),
            MapArrayPropertyTestNullable::class,
        );

        self::assertSame(['a' => 0], $decoded->values);
    }

    public function testAnEmptyMapAndAnEmptyArrayAreBothEmpty(): void
    {
        $reflector = Reflector::build();
        /** @var Map<string, int> $empty */
        $empty = new Map();

        self::assertSame([], $reflector->message(new MapArrayPropertyTestStrings([]))->fields);
        self::assertSame([], $reflector->message(new MapArrayPropertyTestMap($empty))->fields);
        self::assertSame([], $reflector->message(new MapArrayPropertyTestArrayOrMap([]))->fields);
        self::assertSame([], $reflector->message(new MapArrayPropertyTestArrayOrMap($empty))->fields);
    }

    public function testAnIterablePropertyEncodesAnyIterable(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $expected = '0a050a016110010a050a01621002';

        self::assertSame($expected, bin2hex($encoder->encode(new MapArrayPropertyTestIterable(['a' => 1, 'b' => 2]))));
        self::assertSame($expected, bin2hex($encoder->encode(new MapArrayPropertyTestIterable(new \ArrayIterator(['a' => 1, 'b' => 2])))));
        self::assertSame($expected, bin2hex($encoder->encode(new MapArrayPropertyTestIterable(Map::fromArray(['a' => 1, 'b' => 2])))));
        self::assertSame($expected, bin2hex($encoder->encode(new MapArrayPropertyTestIterable((static function (): \Generator {
            yield 'a' => 1;
            yield 'b' => 2;
        })()))));
    }

    public function testAnIterableOfMessagesIsEncodedLikeAMap(): void
    {
        $encoder = Encoder\Builder::buildDefault();

        $expected = $encoder->encode(new MapArrayPropertyTestMessagesMap(Map::fromArray([7 => new MapArrayPropertyTestInner(5)])));

        self::assertSame($expected, $encoder->encode(new MapArrayPropertyTestMessagesIterable(
            new \ArrayIterator([7 => new MapArrayPropertyTestInner(5)]),
        )));
        self::assertSame($expected, $encoder->encode(new MapArrayPropertyTestMessagesIterable(
            [7 => new MapArrayPropertyTestInner(5)],
        )));
    }
}

enum MapArrayPropertyTestKind: int
{
    case Zero = 0;
    case One = 1;
}

final readonly class MapArrayPropertyTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final readonly class MapArrayPropertyTestStrings
{
    /**
     * @param array<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestNullable
{
    /**
     * @param ?array<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public ?array $values = null,
    ) {}
}

final readonly class MapArrayPropertyTestArrayOrString
{
    /**
     * @param array<array-key, int>|string $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public array|string $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestMap
{
    /**
     * @param Map<string, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public Map $values = new Map(),
    ) {}
}

final readonly class MapArrayPropertyTestArrayOrMap
{
    /**
     * @param array<array-key, int>|Map<string, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public array|Map $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestIterable
{
    /**
     * @param iterable<string, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public iterable $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestCountable
{
    /**
     * @param \Countable&\Traversable<string, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public \Countable&\Traversable $values = new Map(),
    ) {}
}

final readonly class MapArrayPropertyTestMixed
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public mixed $values = [],
    ) {}
}

final class MapArrayPropertyTestUntyped
{
    /**
     * @param iterable<string, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestBoolKeys
{
    /**
     * @param array<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\BoolT::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestNullableBoolKeys
{
    /**
     * @param ?array<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\BoolT::T, Reflection\Int32T::T))]
        public ?array $values = null,
    ) {}
}

final readonly class MapArrayPropertyTestUint64Keys
{
    /**
     * @param array<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Uint64T::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestFixed64Keys
{
    /**
     * @param array<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Fixed64T::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestBoolKeysOrMap
{
    /**
     * @param array<array-key, int>|Map<bool, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\BoolT::T, Reflection\Int32T::T))]
        public array|Map $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestUint64KeysIterable
{
    /**
     * @param iterable<\BcMath\Number, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Uint64T::T, Reflection\Int32T::T))]
        public iterable $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestInt64Keys
{
    /**
     * @param array<int, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Int64T::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestInt32Keys
{
    /**
     * @param array<int, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Int32T::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestUint32Keys
{
    /**
     * @param array<int, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Uint32T::T, Reflection\Int32T::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestMessages
{
    /**
     * @param array<int, MapArrayPropertyTestInner> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(MapArrayPropertyTestInner::class)))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestMessagesMap
{
    /**
     * @param Map<int, MapArrayPropertyTestInner> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(MapArrayPropertyTestInner::class)))]
        public Map $values = new Map(),
    ) {}
}

final readonly class MapArrayPropertyTestMessagesIterable
{
    /**
     * @param iterable<int, MapArrayPropertyTestInner> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(MapArrayPropertyTestInner::class)))]
        public iterable $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestEnums
{
    /**
     * @param array<string, MapArrayPropertyTestKind> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, new Reflection\EnumT(MapArrayPropertyTestKind::class)))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestOuter
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(MapArrayPropertyTestStrings::class))]
        public ?MapArrayPropertyTestStrings $inner = null,
    ) {}
}

final readonly class MapArrayPropertyTestVariant
{
    /**
     * @param array<int, string> $values
     */
    public function __construct(
        #[Reflection\Field(2, new Reflection\MapT(Reflection\SInt32T::T, Reflection\StringT::T))]
        public array $values = [],
    ) {}
}

final readonly class MapArrayPropertyTestChoice
{
    public function __construct(
        #[Reflection\OneOf([MapArrayPropertyTestVariant::class])]
        public ?MapArrayPropertyTestVariant $choice = null,
    ) {}
}
