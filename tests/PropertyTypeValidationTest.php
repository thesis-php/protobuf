<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Api\ExpectedPhpType;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToExpectedPhpType;

#[CoversClass(ClassReflector::class)]
#[CoversClass(ExpectedPhpType::class)]
#[CoversClass(ToExpectedPhpType::class)]
#[CoversClass(InvalidProperty::class)]
final class PropertyTypeValidationTest extends TestCase
{
    /**
     * @param class-string $class
     * @param non-empty-string $reason
     */
    #[DataProvider('provideMismatchedPropertyTypeIsRejectedCases')]
    public function testMismatchedPropertyTypeIsRejected(string $class, string $reason): void
    {
        $this->expectExceptionObject(new InvalidProperty($class, 'value', $reason));

        new ClassReflector()->reflect($class);
    }

    /**
     * @return iterable<string, array{class-string, non-empty-string}>
     */
    public static function provideMismatchedPropertyTypeIsRejectedCases(): iterable
    {
        yield 'string for int32' => [
            PropertyTypeValidationTestStringForInt::class,
            'of type string cannot hold the values of the protobuf field 1: expected int',
        ];

        yield 'int for double' => [
            PropertyTypeValidationTestIntForDouble::class,
            'of type int cannot hold the values of the protobuf field 1: expected float',
        ];

        yield 'int for uint64' => [
            PropertyTypeValidationTestIntForUint64::class,
            'of type int cannot hold the values of the protobuf field 1: expected BcMath\Number',
        ];

        yield 'int for bool' => [
            PropertyTypeValidationTestIntForBool::class,
            'of type ?int cannot hold the values of the protobuf field 1: expected bool',
        ];

        yield 'string for list' => [
            PropertyTypeValidationTestStringForList::class,
            'of type string cannot hold the values of the protobuf field 1: expected array',
        ];

        yield 'other enum' => [
            PropertyTypeValidationTestOtherEnum::class,
            \sprintf('of type ?%s cannot hold the values of the protobuf field 1: expected %s', PropertyTypeValidationTestOtherKind::class, PropertyTypeValidationTestKind::class),
        ];

        yield 'other class' => [
            PropertyTypeValidationTestOtherClass::class,
            // The property is typed ?self, which PHP reports as written or as the class name, depending on its version.
            \sprintf(
                'of type %s cannot hold the values of the protobuf field 1: expected %s',
                (string) new \ReflectionProperty(PropertyTypeValidationTestOtherClass::class, 'value')->getType(),
                PropertyTypeValidationTestInner::class,
            ),
        ];

        yield 'string for map' => [
            PropertyTypeValidationTestStringForMap::class,
            \sprintf('of type string cannot hold the values of the protobuf field 1: expected %s', Map::class),
        ];

        yield 'oneof without a variant' => [
            PropertyTypeValidationTestOneOfMissingVariant::class,
            \sprintf('of type ?%s cannot hold the oneof variant %s', PropertyTypeValidationTestInner::class, PropertyTypeValidationTestOtherInner::class),
        ];
    }

    public function testCompatiblePropertyTypesAreAccepted(): void
    {
        $object = new PropertyTypeValidationTestCompatible(
            int: 1,
            float: 2,
            mixed: 'x',
            untyped: 'y',
            number: new Number(3),
            enum: PropertyTypeValidationTestKind::One,
            list: [4],
            map: Map::fromArray(['a' => 5]),
            object: new PropertyTypeValidationTestInner(6),
            self: new PropertyTypeValidationTestCompatible(),
            variant: new PropertyTypeValidationTestVariantB(7),
        );

        $bytes = Encoder\Builder::buildDefault()->encode($object);
        $decoded = Decoder\Builder::buildDefault()->decode($bytes, PropertyTypeValidationTestCompatible::class);

        self::assertSame($bytes, Encoder\Builder::buildDefault()->encode($decoded));
        self::assertSame(2.0, $decoded->float);
        self::assertEquals(new PropertyTypeValidationTestVariantB(7), $decoded->variant);
    }
}

enum PropertyTypeValidationTestKind: int
{
    case Zero = 0;
    case One = 1;
}

enum PropertyTypeValidationTestOtherKind: int
{
    case Zero = 0;
}

interface PropertyTypeValidationTestVariant {}

final readonly class PropertyTypeValidationTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final readonly class PropertyTypeValidationTestOtherInner
{
    public function __construct(
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $other = 0,
    ) {}
}

final readonly class PropertyTypeValidationTestVariantA implements PropertyTypeValidationTestVariant
{
    public function __construct(
        #[Reflection\Field(11, Reflection\Int32T::T)]
        public int $a = 0,
    ) {}
}

final readonly class PropertyTypeValidationTestVariantB implements PropertyTypeValidationTestVariant
{
    public function __construct(
        #[Reflection\Field(12, Reflection\Int32T::T)]
        public int $b = 0,
    ) {}
}

final readonly class PropertyTypeValidationTestStringForInt
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public string $value = '',
    ) {}
}

final readonly class PropertyTypeValidationTestIntForDouble
{
    public function __construct(
        #[Reflection\Field(1, Reflection\DoubleT::T)]
        public int $value = 0,
    ) {}
}

final readonly class PropertyTypeValidationTestIntForUint64
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Uint64T::T)]
        public int $value = 0,
    ) {}
}

final readonly class PropertyTypeValidationTestIntForBool
{
    public function __construct(
        #[Reflection\Field(1, Reflection\BoolT::T)]
        public ?int $value = null,
    ) {}
}

final readonly class PropertyTypeValidationTestStringForList
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T))]
        public string $value = '',
    ) {}
}

final readonly class PropertyTypeValidationTestOtherEnum
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(PropertyTypeValidationTestKind::class))]
        public ?PropertyTypeValidationTestOtherKind $value = null,
    ) {}
}

final readonly class PropertyTypeValidationTestOtherClass
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(PropertyTypeValidationTestInner::class))]
        public ?self $value = null,
    ) {}
}

final readonly class PropertyTypeValidationTestStringForMap
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public string $value = '',
    ) {}
}

final readonly class PropertyTypeValidationTestOneOfMissingVariant
{
    public function __construct(
        #[Reflection\OneOf([PropertyTypeValidationTestInner::class, PropertyTypeValidationTestOtherInner::class])]
        public ?PropertyTypeValidationTestInner $value = null,
    ) {}
}

final class PropertyTypeValidationTestCompatible
{
    /**
     * @param string $untyped
     * @param iterable<int> $list
     * @param \Countable&\Traversable<string, int> $map
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public int|string $int = 0,
        #[Reflection\Field(2, Reflection\DoubleT::T)]
        public int|float $float = 0,
        #[Reflection\Field(3, Reflection\StringT::T)]
        public mixed $mixed = '',
        #[Reflection\Field(4, Reflection\StringT::T)]
        public $untyped = '',
        #[Reflection\Field(5, Reflection\Uint64T::T)]
        public ?\Stringable $number = null,
        #[Reflection\Field(6, new Reflection\EnumT(PropertyTypeValidationTestKind::class))]
        public ?\BackedEnum $enum = null,
        #[Reflection\Field(7, new Reflection\ListT(Reflection\Int64T::T))]
        public iterable $list = [],
        #[Reflection\Field(8, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public \Countable&\Traversable $map = new Map(),
        #[Reflection\Field(9, new Reflection\ObjectT(PropertyTypeValidationTestInner::class))]
        public ?object $object = null,
        #[Reflection\Field(10, new Reflection\ObjectT(self::class))]
        public ?self $self = null,
        #[Reflection\OneOf([PropertyTypeValidationTestVariantA::class, PropertyTypeValidationTestVariantB::class])]
        public ?PropertyTypeValidationTestVariant $variant = null,
    ) {}
}
