<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Exception\MappingError;
use Thesis\Protobuf\Reflection\Exception\PropertyRequired;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToZeroValueTypeVisitor;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(Decoder\Builder::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(ToZeroValueTypeVisitor::class)]
final class MissingFieldsAsZeroValuesTest extends TestCase
{
    public function testAZeroFromAProto3SenderFailsWithoutTheOption(): void
    {
        // A proto3 sender writes no zero value at all.
        $bytes = Encoder\Builder::buildDefault()->encode(new MissingFieldsAsZeroValuesTestPointSender(0, 0));
        self::assertSame('', $bytes);

        self::assertSame(['x', 'y'], self::requiredProperties(
            Decoder\Builder::buildDefault(),
            $bytes,
            MissingFieldsAsZeroValuesTestPoint::class,
        ));
    }

    public function testWithoutTheOptionEveryMissingFieldWithoutADefaultIsRequired(): void
    {
        self::assertSame(
            [
                'boolean',
                'bytes',
                'double',
                'enumWithZero',
                'enumWithoutZero',
                'fixed32',
                'fixed64',
                'float',
                'int32',
                'int64',
                'list',
                'map',
                'mapArray',
                'sfixed32',
                'sfixed64',
                'sint32',
                'sint64',
                'string',
                'uint32',
                'uint64',
            ],
            self::requiredProperties(Decoder\Builder::buildDefault(), '', MissingFieldsAsZeroValuesTestScalars::class),
        );
    }

    public function testMissingFieldsGetTheZeroValuesOfTheirTypes(): void
    {
        $object = self::decoder()->decode('', MissingFieldsAsZeroValuesTestScalars::class);

        self::assertSame(0, $object->int32);
        self::assertSame(0, $object->uint32);
        self::assertSame(0, $object->sint32);
        self::assertSame(0, $object->int64);
        self::assertSame('0', (string) $object->uint64);
        self::assertSame(0, $object->sint64);
        self::assertSame(0, $object->fixed32);
        self::assertSame(0, $object->sfixed32);
        self::assertSame('0', (string) $object->fixed64);
        self::assertSame(0, $object->sfixed64);
        self::assertSame(0.0, $object->float);
        self::assertSame(0.0, $object->double);
        self::assertFalse($object->boolean);
        self::assertSame('', $object->string);
        self::assertSame('', $object->bytes);
        self::assertSame(MissingFieldsAsZeroValuesTestStatus::Unknown, $object->enumWithZero);
        // As for a map entry without a value: an enum without a case for 0 gives its first case.
        self::assertSame(MissingFieldsAsZeroValuesTestLevel::Low, $object->enumWithoutZero);
        self::assertSame([], $object->list);
        self::assertCount(0, $object->map);
        self::assertSame([], $object->mapArray);
    }

    public function testEachObjectGetsAMapOfItsOwn(): void
    {
        $decoder = self::decoder();

        $first = $decoder->decode('', MissingFieldsAsZeroValuesTestScalars::class);
        $second = $decoder->decode('', MissingFieldsAsZeroValuesTestScalars::class);

        self::assertNotSame($first->map, $second->map);
    }

    public function testAPresentFieldIsNotReplaced(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new MissingFieldsAsZeroValuesTestPointSender(5, 0));

        $point = self::decoder()->decode($bytes, MissingFieldsAsZeroValuesTestPoint::class);

        self::assertSame(5, $point->x);
        self::assertSame(0, $point->y);
    }

    public function testADefaultAndNullComeBeforeTheZeroValue(): void
    {
        $object = self::decoder()->decode('', MissingFieldsAsZeroValuesTestDefaults::class);

        self::assertSame(7, $object->withDefault);
        self::assertSame(MissingFieldsAsZeroValuesTestStatus::Active, $object->enumWithDefault);
        self::assertNull($object->nullable);
        self::assertNull($object->nullableObject);
        self::assertNull($object->oneof);
    }

    public function testNestedObjectsGetTheZeroValuesOfTheirMissingFields(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new MissingFieldsAsZeroValuesTestOuterSender(
            inner: new MissingFieldsAsZeroValuesTestInnerSender(),
            items: [new MissingFieldsAsZeroValuesTestInnerSender(4), new MissingFieldsAsZeroValuesTestInnerSender()],
            byKey: [1 => new MissingFieldsAsZeroValuesTestInnerSender(), 2 => new MissingFieldsAsZeroValuesTestInnerSender(6)],
            variant: new MissingFieldsAsZeroValuesTestVariantSender(3),
        ));

        $outer = self::decoder()->decode($bytes, MissingFieldsAsZeroValuesTestOuter::class);

        self::assertEquals(new MissingFieldsAsZeroValuesTestInner(0, ''), $outer->inner);
        self::assertEquals(
            [new MissingFieldsAsZeroValuesTestInner(4, ''), new MissingFieldsAsZeroValuesTestInner(0, '')],
            $outer->items,
        );
        self::assertEquals(
            [1 => new MissingFieldsAsZeroValuesTestInner(0, ''), 2 => new MissingFieldsAsZeroValuesTestInner(6, '')],
            iterator_to_array($outer->byKey),
        );
        self::assertEquals(new MissingFieldsAsZeroValuesTestVariant(3, ''), $outer->variant);
    }

    public function testAMessageFieldStaysRequired(): void
    {
        self::assertSame(['inner'], self::requiredProperties(
            self::decoder(),
            '',
            MissingFieldsAsZeroValuesTestMessageHolder::class,
        ));
    }

    public function testAnEnumWithoutCasesStaysRequired(): void
    {
        self::assertSame(['nothing'], self::requiredProperties(
            self::decoder(),
            '',
            MissingFieldsAsZeroValuesTestNothingHolder::class,
        ));
    }

    public function testABoolPropertyThatCannotBeFalseStaysRequired(): void
    {
        self::assertSame(['on'], self::requiredProperties(
            self::decoder(),
            '',
            MissingFieldsAsZeroValuesTestTrue::class,
        ));
    }

    public function testABoolPropertyThatCanOnlyBeFalseGetsFalse(): void
    {
        self::assertEquals(
            new MissingFieldsAsZeroValuesTestFalse(false),
            self::decoder()->decode('', MissingFieldsAsZeroValuesTestFalse::class),
        );
    }

    public function testAOneofThatCannotBeNullFailsWithoutAVariantAsWithoutTheOption(): void
    {
        // The oneof gets no zero variant: null is set, which the property does not accept.
        $this->expectException(\TypeError::class);

        self::decoder()->decode('', MissingFieldsAsZeroValuesTestOneOfHolder::class);
    }

    public function testTheBuilderIsNotChanged(): void
    {
        $builder = new Decoder\Builder();
        $withZeroValues = $builder->withMissingFieldsAsZeroValues(true);

        self::assertNotSame($builder, $withZeroValues);
        self::assertSame(['x', 'y'], self::requiredProperties($builder->build(), '', MissingFieldsAsZeroValuesTestPoint::class));
        self::assertSame(['x', 'y'], self::requiredProperties(
            $withZeroValues->withMissingFieldsAsZeroValues(false)->build(),
            '',
            MissingFieldsAsZeroValuesTestPoint::class,
        ));
        self::assertEquals(
            new MissingFieldsAsZeroValuesTestPoint(0, 0),
            $withZeroValues->build()->decode('', MissingFieldsAsZeroValuesTestPoint::class),
        );
    }

    public function testTheReflectorTakesTheOption(): void
    {
        self::assertEquals(
            new MissingFieldsAsZeroValuesTestPoint(0, 2),
            Reflector::build(missingFieldsAsZeroValues: true)->map(
                message(fieldOf(2, int32Of(2))),
                MissingFieldsAsZeroValuesTestPoint::class,
            ),
        );

        try {
            Reflector::build()->map(message(fieldOf(2, int32Of(2))), MissingFieldsAsZeroValuesTestPoint::class);
            self::fail('Mapping must fail.');
        } catch (MappingError $e) {
            self::assertCount(1, $e->reasons);
            self::assertInstanceOf(PropertyRequired::class, $e->reasons[0]);
            self::assertSame('x', $e->reasons[0]->property);
        }
    }

    private static function decoder(): Decoder
    {
        return new Decoder\Builder()->withMissingFieldsAsZeroValues(true)->build();
    }

    /**
     * @param class-string $class
     * @return list<string> the properties that decoding the bytes fails for as required, sorted
     */
    private static function requiredProperties(Decoder $decoder, string $bytes, string $class): array
    {
        try {
            $decoder->decode($bytes, $class);
        } catch (MappingError $e) {
            $required = array_map(
                static fn(\Throwable $reason): string => $reason instanceof PropertyRequired ? $reason->property : $reason::class,
                $e->reasons,
            );
            sort($required);

            return $required;
        }

        self::fail('Decoding must fail.');
    }
}

enum MissingFieldsAsZeroValuesTestStatus: int
{
    case Active = 1;
    case Unknown = 0;
}

enum MissingFieldsAsZeroValuesTestLevel: int
{
    case Low = 1;
    case High = 2;
}

enum MissingFieldsAsZeroValuesTestNothing: int {}

final readonly class MissingFieldsAsZeroValuesTestScalars
{
    /**
     * @param list<int> $list
     * @param Map<string, int> $map
     * @param array<string, int> $mapArray
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $int32,
        #[Reflection\Field(2, Reflection\Uint32T::T)]
        public int $uint32,
        #[Reflection\Field(3, Reflection\SInt32T::T)]
        public int $sint32,
        #[Reflection\Field(4, Reflection\Int64T::T)]
        public int $int64,
        #[Reflection\Field(5, Reflection\Uint64T::T)]
        public Number $uint64,
        #[Reflection\Field(6, Reflection\SInt64T::T)]
        public int $sint64,
        #[Reflection\Field(7, Reflection\Fixed32T::T)]
        public int $fixed32,
        #[Reflection\Field(8, Reflection\SFixed32T::T)]
        public int $sfixed32,
        #[Reflection\Field(9, Reflection\Fixed64T::T)]
        public Number $fixed64,
        #[Reflection\Field(10, Reflection\SFixed64T::T)]
        public int $sfixed64,
        #[Reflection\Field(11, Reflection\FloatT::T)]
        public float $float,
        #[Reflection\Field(12, Reflection\DoubleT::T)]
        public float $double,
        #[Reflection\Field(13, Reflection\BoolT::T)]
        public bool $boolean,
        #[Reflection\Field(14, Reflection\StringT::T)]
        public string $string,
        #[Reflection\Field(15, Reflection\BytesT::T)]
        public string $bytes,
        #[Reflection\Field(16, new Reflection\EnumT(MissingFieldsAsZeroValuesTestStatus::class))]
        public MissingFieldsAsZeroValuesTestStatus $enumWithZero,
        #[Reflection\Field(17, new Reflection\EnumT(MissingFieldsAsZeroValuesTestLevel::class))]
        public MissingFieldsAsZeroValuesTestLevel $enumWithoutZero,
        #[Reflection\Field(18, new Reflection\ListT(Reflection\Int32T::T))]
        public array $list,
        #[Reflection\Field(19, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public Map $map,
        #[Reflection\Field(20, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public array $mapArray,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestPoint
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $x,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $y,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestPointSender
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $x = 0,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $y = 0,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestDefaults
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $nullable,
        #[Reflection\Field(2, new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInner::class))]
        public ?MissingFieldsAsZeroValuesTestInner $nullableObject,
        #[Reflection\OneOf([MissingFieldsAsZeroValuesTestVariant::class])]
        public ?MissingFieldsAsZeroValuesTestVariant $oneof,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public int $withDefault = 7,
        #[Reflection\Field(4, new Reflection\EnumT(MissingFieldsAsZeroValuesTestStatus::class))]
        public MissingFieldsAsZeroValuesTestStatus $enumWithDefault = MissingFieldsAsZeroValuesTestStatus::Active,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $a,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $b,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestInnerSender
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $a = 0,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestVariant
{
    public function __construct(
        #[Reflection\Field(10, Reflection\Int32T::T)]
        public int $p,
        #[Reflection\Field(11, Reflection\StringT::T)]
        public string $q,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestVariantSender
{
    public function __construct(
        #[Reflection\Field(10, Reflection\Int32T::T)]
        public int $p = 0,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestOuter
{
    /**
     * @param list<MissingFieldsAsZeroValuesTestInner> $items
     * @param Map<int, MissingFieldsAsZeroValuesTestInner> $byKey
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInner::class))]
        public MissingFieldsAsZeroValuesTestInner $inner,
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInner::class)))]
        public array $items,
        #[Reflection\Field(3, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInner::class)))]
        public Map $byKey,
        #[Reflection\OneOf([MissingFieldsAsZeroValuesTestVariant::class])]
        public ?MissingFieldsAsZeroValuesTestVariant $variant = null,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestOuterSender
{
    /**
     * @param list<MissingFieldsAsZeroValuesTestInnerSender> $items
     * @param array<int, MissingFieldsAsZeroValuesTestInnerSender> $byKey
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInnerSender::class))]
        public ?MissingFieldsAsZeroValuesTestInnerSender $inner = null,
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInnerSender::class)))]
        public array $items = [],
        #[Reflection\Field(3, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInnerSender::class)))]
        public array $byKey = [],
        #[Reflection\OneOf([MissingFieldsAsZeroValuesTestVariantSender::class])]
        public ?MissingFieldsAsZeroValuesTestVariantSender $variant = null,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestMessageHolder
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(MissingFieldsAsZeroValuesTestInner::class))]
        public MissingFieldsAsZeroValuesTestInner $inner,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $count,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestNothingHolder
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(MissingFieldsAsZeroValuesTestNothing::class))]
        public MissingFieldsAsZeroValuesTestNothing $nothing,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $count,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestTrue
{
    public function __construct(
        #[Reflection\Field(1, Reflection\BoolT::T)]
        public true $on,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $count,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestFalse
{
    public function __construct(
        #[Reflection\Field(1, Reflection\BoolT::T)]
        public false $off,
    ) {}
}

final readonly class MissingFieldsAsZeroValuesTestOneOfHolder
{
    public function __construct(
        #[Reflection\OneOf([MissingFieldsAsZeroValuesTestVariant::class])]
        public MissingFieldsAsZeroValuesTestVariant $variant,
    ) {}
}
