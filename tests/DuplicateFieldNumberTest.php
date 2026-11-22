<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Internal\Serde\OneOfs;
use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Type\MessageT;

#[CoversClass(Message::class)]
#[CoversClass(MessageT::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(OneOfs::class)]
final class DuplicateFieldNumberTest extends TestCase
{
    public function testAMessageWithTwoFieldsOfOneNumberIsRejected(): void
    {
        $this->expectExceptionObject(new InvalidSchema(
            'Field number 1 is given more than once: the fields of a message must have distinct numbers.',
        ));

        message(fieldOf(1, int32Of(1)), fieldOf(2, int32Of(2)), fieldOf(1, int32Of(3)));
    }

    public function testAMessageValueWithTwoFieldsOfOneNumberIsRejected(): void
    {
        $this->expectException(InvalidSchema::class);

        messageOf(fieldOf(3, stringOf('a')), fieldOf(3, stringOf('b')));
    }

    public function testAMessageWithDistinctNumbersKeepsEveryField(): void
    {
        $message = message(fieldOf(2, int32Of(2)), fieldOf(1, int32Of(1)));

        self::assertCount(2, $message);
        self::assertSame('10020801', bin2hex(new Serializer()->serialize($message)));
    }

    public function testAMessageTypeWithTwoFieldsOfOneNumberIsRejected(): void
    {
        $this->expectExceptionObject(new InvalidSchema(
            'Field number 2 is declared more than once: the fields of a message type must have distinct numbers.',
        ));

        messageT(fieldT(2, int32T), fieldT(1, int32T), fieldT(2, stringT));
    }

    public function testAMessageTypeWithDistinctNumbersKeepsEveryField(): void
    {
        self::assertSame([2, 1], array_keys(messageT(fieldT(2, int32T), fieldT(1, stringT))->fields));
    }

    /**
     * @param class-string $class
     * @param non-empty-string $property
     * @param non-empty-string $reason
     * @param ?class-string $declaringClass the class that declares the property, if not the class itself
     */
    #[DataProvider('provideAClassDeclaringANumberTwiceIsRejectedCases')]
    public function testAClassDeclaringANumberTwiceIsRejectedWhenItsTypeIsBuilt(string $class, string $property, string $reason, ?string $declaringClass = null): void
    {
        $this->expectExceptionObject(new InvalidProperty($declaringClass ?? $class, $property, $reason));

        Reflector::build()->type($class);
    }

    /**
     * @param class-string $class
     * @param non-empty-string $property
     * @param non-empty-string $reason
     * @param ?class-string $declaringClass the class that declares the property, if not the class itself
     */
    #[DataProvider('provideAClassDeclaringANumberTwiceIsRejectedCases')]
    public function testAClassDeclaringANumberTwiceIsRejectedWhenEncoded(string $class, string $property, string $reason, ?string $declaringClass = null): void
    {
        $this->expectExceptionObject(new InvalidProperty($declaringClass ?? $class, $property, $reason));

        // Every property holds its default value: nothing would be written twice.
        Encoder\Builder::buildDefault()->encode(new $class());
    }

    /**
     * @param class-string $class
     * @param non-empty-string $property
     * @param non-empty-string $reason
     * @param ?class-string $declaringClass the class that declares the property, if not the class itself
     */
    #[DataProvider('provideAClassDeclaringANumberTwiceIsRejectedCases')]
    public function testAClassDeclaringANumberTwiceIsRejectedWhenDecoded(string $class, string $property, string $reason, ?string $declaringClass = null): void
    {
        $this->expectExceptionObject(new InvalidProperty($declaringClass ?? $class, $property, $reason));

        Decoder\Builder::buildDefault()->decode('', $class);
    }

    /**
     * @param class-string $class
     * @param non-empty-string $property
     * @param non-empty-string $reason
     * @param ?class-string $declaringClass the class that declares the property, if not the class itself
     */
    #[DataProvider('provideAClassDeclaringANumberTwiceIsRejectedCases')]
    public function testAClassDeclaringANumberTwiceIsRejectedWhenMapped(string $class, string $property, string $reason, ?string $declaringClass = null): void
    {
        $this->expectExceptionObject(new InvalidProperty($declaringClass ?? $class, $property, $reason));

        Reflector::build()->map(message(), $class);
    }

    /**
     * @return iterable<string, array{0: class-string, 1: non-empty-string, 2: non-empty-string, 3?: class-string}>
     */
    public static function provideAClassDeclaringANumberTwiceIsRejectedCases(): iterable
    {
        yield 'two properties' => [
            DuplicateFieldNumberTestTwoFields::class,
            'b',
            \sprintf('declares the field number 1, which "%s::$a" declares too', DuplicateFieldNumberTestTwoFields::class),
        ];

        yield 'a property and a later oneof variant' => [
            DuplicateFieldNumberTestFieldThenOneOf::class,
            'choice',
            \sprintf(
                'declares the field number 1 through "%1$s::$choice → %2$s::$number", which "%1$s::$id" declares too',
                DuplicateFieldNumberTestFieldThenOneOf::class,
                DuplicateFieldNumberTestOne::class,
            ),
        ];

        yield 'a oneof variant and a later property' => [
            DuplicateFieldNumberTestOneOfThenField::class,
            'id',
            \sprintf(
                'declares the field number 1, which "%1$s::$choice → %2$s::$number" declares too',
                DuplicateFieldNumberTestOneOfThenField::class,
                DuplicateFieldNumberTestOne::class,
            ),
        ];

        yield 'two variants of one oneof' => [
            DuplicateFieldNumberTestTwoVariants::class,
            'choice',
            \sprintf(
                'declares the field number 1 through "%1$s::$choice → %3$s::$text", which "%1$s::$choice → %2$s::$number" declares too',
                DuplicateFieldNumberTestTwoVariants::class,
                DuplicateFieldNumberTestOne::class,
                DuplicateFieldNumberTestAlsoOne::class,
            ),
        ];

        yield 'variants of two oneofs' => [
            DuplicateFieldNumberTestTwoOneOfs::class,
            'other',
            \sprintf(
                'declares the field number 1 through "%1$s::$other → %3$s::$text", which "%1$s::$choice → %2$s::$number" declares too',
                DuplicateFieldNumberTestTwoOneOfs::class,
                DuplicateFieldNumberTestOne::class,
                DuplicateFieldNumberTestAlsoOne::class,
            ),
        ];

        yield 'one variant in two oneofs' => [
            DuplicateFieldNumberTestSameVariant::class,
            'other',
            \sprintf(
                'declares the field number 1 through "%1$s::$other → %2$s::$number", which "%1$s::$choice → %2$s::$number" declares too',
                DuplicateFieldNumberTestSameVariant::class,
                DuplicateFieldNumberTestOne::class,
            ),
        ];

        yield 'a oneof of a variant' => [
            DuplicateFieldNumberTestNestedOneOf::class,
            'choice',
            \sprintf(
                'declares the field number 1 through "%1$s::$choice → %2$s::$inner → %3$s::$number", which "%1$s::$id" declares too',
                DuplicateFieldNumberTestNestedOneOf::class,
                DuplicateFieldNumberTestWithOneOf::class,
                DuplicateFieldNumberTestOne::class,
            ),
        ];

        // The properties of the class come before those of its parent, and each is named after the class declaring it.
        yield 'a property of a parent class' => [
            DuplicateFieldNumberTestChild::class,
            'a',
            \sprintf(
                'declares the field number 1, which "%s::$b" declares too',
                DuplicateFieldNumberTestChild::class,
            ),
            DuplicateFieldNumberTestParent::class,
        ];

        yield 'a class that holds itself' => [
            DuplicateFieldNumberTestRecursive::class,
            'value',
            \sprintf('declares the field number 1, which "%s::$next" declares too', DuplicateFieldNumberTestRecursive::class),
        ];
    }

    public function testAClassHoldingAClassThatDeclaresANumberTwiceIsRejected(): void
    {
        $reason = \sprintf('declares the field number 1, which "%s::$a" declares too', DuplicateFieldNumberTestTwoFields::class);
        $expected = new InvalidProperty(DuplicateFieldNumberTestTwoFields::class, 'b', $reason);

        try {
            Reflector::build()->type(DuplicateFieldNumberTestOuter::class);
            self::fail('The type was built.');
        } catch (InvalidProperty $e) {
            self::assertEquals($expected, $e);
        }

        try {
            // The nested property is null: the nested class is never encoded.
            Encoder\Builder::buildDefault()->encode(new DuplicateFieldNumberTestOuter());
            self::fail('The object was encoded.');
        } catch (InvalidProperty $e) {
            self::assertEquals($expected, $e);
        }

        $this->expectExceptionObject($expected);

        Decoder\Builder::buildDefault()->decode('', DuplicateFieldNumberTestOuter::class);
    }

    public function testAClassIsRejectedAgainWhenReflectedAgain(): void
    {
        $reflector = Reflector::build();

        try {
            $reflector->type(DuplicateFieldNumberTestTwoFields::class);
            self::fail('The type was built.');
        } catch (InvalidProperty) {
        }

        $this->expectException(InvalidProperty::class);

        $reflector->type(DuplicateFieldNumberTestTwoFields::class);
    }

    public function testAClassAsTheTypeOfTwoFieldsIsAccepted(): void
    {
        $object = new DuplicateFieldNumberTestPair(new DuplicateFieldNumberTestOne(1), new DuplicateFieldNumberTestOne(2));

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertSame('0a02080112020802', bin2hex($bytes));
        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, DuplicateFieldNumberTestPair::class));
    }

    public function testAClassThatHoldsItselfIsAccepted(): void
    {
        $object = new DuplicateFieldNumberTestNode(1, new DuplicateFieldNumberTestNode(2, new DuplicateFieldNumberTestNode(3)));

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, DuplicateFieldNumberTestNode::class));
    }

    public function testOneOfsWithDistinctNumbersKeepEveryVariant(): void
    {
        $object = new DuplicateFieldNumberTestDistinctOneOfs(
            new DuplicateFieldNumberTestOne(5),
            new DuplicateFieldNumberTestTwo('a'),
        );

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertSame('0805120161', bin2hex($bytes));
        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, DuplicateFieldNumberTestDistinctOneOfs::class));
    }
}

final readonly class DuplicateFieldNumberTestTwoFields
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $a = 0,
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $b = '',
    ) {}
}

interface DuplicateFieldNumberTestChoice {}

final readonly class DuplicateFieldNumberTestOne implements DuplicateFieldNumberTestChoice
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $number = 0,
    ) {}
}

final readonly class DuplicateFieldNumberTestAlsoOne implements DuplicateFieldNumberTestChoice
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $text = '',
    ) {}
}

final readonly class DuplicateFieldNumberTestTwo implements DuplicateFieldNumberTestChoice
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $text = '',
    ) {}
}

final readonly class DuplicateFieldNumberTestFieldThenOneOf
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([DuplicateFieldNumberTestTwo::class, DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestOneOfThenField
{
    public function __construct(
        #[Reflection\OneOf([DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
    ) {}
}

final readonly class DuplicateFieldNumberTestTwoVariants
{
    public function __construct(
        #[Reflection\OneOf([DuplicateFieldNumberTestOne::class, DuplicateFieldNumberTestAlsoOne::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestTwoOneOfs
{
    public function __construct(
        #[Reflection\OneOf([DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
        #[Reflection\OneOf([DuplicateFieldNumberTestTwo::class, DuplicateFieldNumberTestAlsoOne::class])]
        public ?DuplicateFieldNumberTestChoice $other = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestSameVariant
{
    public function __construct(
        #[Reflection\OneOf([DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
        #[Reflection\OneOf([DuplicateFieldNumberTestTwo::class, DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $other = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestDistinctOneOfs
{
    public function __construct(
        #[Reflection\OneOf([DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
        #[Reflection\OneOf([DuplicateFieldNumberTestTwo::class])]
        public ?DuplicateFieldNumberTestChoice $other = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestWithOneOf implements DuplicateFieldNumberTestChoice
{
    public function __construct(
        #[Reflection\OneOf([DuplicateFieldNumberTestOne::class])]
        public ?DuplicateFieldNumberTestChoice $inner = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestNestedOneOf
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([DuplicateFieldNumberTestTwo::class, DuplicateFieldNumberTestWithOneOf::class])]
        public ?DuplicateFieldNumberTestChoice $choice = null,
    ) {}
}

abstract class DuplicateFieldNumberTestParent
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $a = 0,
    ) {}
}

final class DuplicateFieldNumberTestChild extends DuplicateFieldNumberTestParent
{
    #[Reflection\Field(1, Reflection\StringT::T)]
    public string $b = '';
}

final readonly class DuplicateFieldNumberTestRecursive
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final readonly class DuplicateFieldNumberTestOuter
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(DuplicateFieldNumberTestTwoFields::class))]
        public ?DuplicateFieldNumberTestTwoFields $inner = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestPair
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(DuplicateFieldNumberTestOne::class))]
        public ?DuplicateFieldNumberTestOne $first = null,
        #[Reflection\Field(2, new Reflection\ObjectT(DuplicateFieldNumberTestOne::class))]
        public ?DuplicateFieldNumberTestOne $second = null,
    ) {}
}

final readonly class DuplicateFieldNumberTestNode
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
    ) {}
}
