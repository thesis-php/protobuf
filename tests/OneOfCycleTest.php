<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(Reflector::class)]
final class OneOfCycleTest extends TestCase
{
    /**
     * @param class-string $class
     * @param class-string $declaringClass
     * @param non-empty-string $property
     * @param non-empty-string $cycle
     */
    #[DataProvider('provideAOneofThatHoldsItselfIsRejectedCases')]
    public function testAOneofThatHoldsItselfIsRejectedWhenTheTypeIsBuilt(string $class, string $declaringClass, string $property, string $cycle): void
    {
        $this->expectExceptionObject(self::cycle($declaringClass, $property, $cycle));

        Reflector::build()->type($class);
    }

    /**
     * @param class-string $class
     * @param class-string $declaringClass
     * @param non-empty-string $property
     * @param non-empty-string $cycle
     */
    #[DataProvider('provideAOneofThatHoldsItselfIsRejectedCases')]
    public function testAOneofThatHoldsItselfIsRejectedWhenEncoded(string $class, string $declaringClass, string $property, string $cycle): void
    {
        $this->expectExceptionObject(self::cycle($declaringClass, $property, $cycle));

        Encoder\Builder::buildDefault()->encode(new $class());
    }

    /**
     * @param class-string $class
     * @param class-string $declaringClass
     * @param non-empty-string $property
     * @param non-empty-string $cycle
     */
    #[DataProvider('provideAOneofThatHoldsItselfIsRejectedCases')]
    public function testAOneofThatHoldsItselfIsRejectedWhenDecoded(string $class, string $declaringClass, string $property, string $cycle): void
    {
        $this->expectExceptionObject(self::cycle($declaringClass, $property, $cycle));

        Decoder\Builder::buildDefault()->decode('', $class);
    }

    /**
     * @param class-string $class
     * @param class-string $declaringClass
     * @param non-empty-string $property
     * @param non-empty-string $cycle
     */
    #[DataProvider('provideAOneofThatHoldsItselfIsRejectedCases')]
    public function testAOneofThatHoldsItselfIsRejectedWhenMapped(string $class, string $declaringClass, string $property, string $cycle): void
    {
        $this->expectExceptionObject(self::cycle($declaringClass, $property, $cycle));

        Reflector::build()->map(message(), $class);
    }

    /**
     * @return iterable<string, array{class-string, class-string, non-empty-string, non-empty-string}>
     */
    public static function provideAOneofThatHoldsItselfIsRejectedCases(): iterable
    {
        $self = OneOfCycleTestSelf::class;

        yield 'a class as a variant of its own oneof' => [$self, $self, 'choice', "{$self}::\$choice → {$self}"];

        $a = OneOfCycleTestA::class;
        $b = OneOfCycleTestB::class;

        yield 'two classes, from the first' => [$a, $b, 'y', "{$a}::\$x → {$b}::\$y → {$a}"];
        yield 'two classes, from the second' => [$b, $a, 'x', "{$b}::\$y → {$a}::\$x → {$b}"];

        $first = OneOfCycleTestFirst::class;
        $second = OneOfCycleTestSecond::class;
        $third = OneOfCycleTestThird::class;

        yield 'three classes' => [$first, $third, 'next', "{$first}::\$next → {$second}::\$next → {$third}::\$next → {$first}"];

        yield 'a class that leads into a cycle' => [OneOfCycleTestEntry::class, $b, 'y', "{$a}::\$x → {$b}::\$y → {$a}"];
    }

    public function testAClassIsRejectedAgainAndOtherClassesAreReflectedAfterwards(): void
    {
        $self = OneOfCycleTestSelf::class;
        $expected = self::cycle($self, 'choice', "{$self}::\$choice → {$self}");
        $reflector = Reflector::build();

        foreach ([1, 2] as $_) {
            try {
                $reflector->type($self);
                self::fail('The type was built.');
            } catch (InvalidProperty $e) {
                self::assertEquals($expected, $e);
            }
        }

        try {
            $reflector->type(OneOfCycleTestEntry::class);
            self::fail('The type was built.');
        } catch (InvalidProperty) {
        }

        // The cycle is told from the class reflected, not from where an earlier reflection left it.
        $a = OneOfCycleTestA::class;
        $b = OneOfCycleTestB::class;

        try {
            $reflector->type($b);
            self::fail('The type was built.');
        } catch (InvalidProperty $e) {
            self::assertEquals(self::cycle($a, 'x', "{$b}::\$y → {$a}::\$x → {$b}"), $e);
        }

        self::assertSame([1, 2], array_keys($reflector->type(OneOfCycleTestHolder::class)->fields));
        self::assertEquals(
            new OneOfCycleTestHolder(7),
            $reflector->map(message(fieldOf(1, int32Of(7))), OneOfCycleTestHolder::class),
        );
    }

    public function testAMessageFieldOfItsOwnClassIsAccepted(): void
    {
        $object = new OneOfCycleTestNode(1, new OneOfCycleTestNode(2, new OneOfCycleTestNode(3)));

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertSame('08011206080212020803', bin2hex($bytes));
        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, OneOfCycleTestNode::class));
    }

    public function testAVariantWithAMessageFieldOfTheClassOfItsOneofIsAccepted(): void
    {
        $object = new OneOfCycleTestHolder(1, new OneOfCycleTestBackRef(new OneOfCycleTestHolder(2)));

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertSame('080112020802', bin2hex($bytes));
        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, OneOfCycleTestHolder::class));
    }

    /**
     * @param class-string $class
     * @param non-empty-string $property
     * @param non-empty-string $cycle
     */
    private static function cycle(string $class, string $property, string $cycle): InvalidProperty
    {
        return new InvalidProperty($class, $property, \sprintf(
            'has a oneof variant that holds the oneof again, which would give the message endless fields: "%s"',
            $cycle,
        ));
    }
}

final readonly class OneOfCycleTestSelf
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([self::class])]
        public ?self $choice = null,
    ) {}
}

final readonly class OneOfCycleTestA
{
    public function __construct(
        #[Reflection\OneOf([OneOfCycleTestB::class])]
        public ?OneOfCycleTestB $x = null,
    ) {}
}

final readonly class OneOfCycleTestB
{
    public function __construct(
        #[Reflection\OneOf([OneOfCycleTestA::class])]
        public ?OneOfCycleTestA $y = null,
    ) {}
}

final readonly class OneOfCycleTestFirst
{
    public function __construct(
        #[Reflection\OneOf([OneOfCycleTestSecond::class])]
        public ?OneOfCycleTestSecond $next = null,
    ) {}
}

final readonly class OneOfCycleTestSecond
{
    public function __construct(
        #[Reflection\OneOf([OneOfCycleTestThird::class])]
        public ?OneOfCycleTestThird $next = null,
    ) {}
}

final readonly class OneOfCycleTestThird
{
    public function __construct(
        #[Reflection\OneOf([OneOfCycleTestFirst::class])]
        public ?OneOfCycleTestFirst $next = null,
    ) {}
}

final readonly class OneOfCycleTestEntry
{
    public function __construct(
        #[Reflection\OneOf([OneOfCycleTestA::class])]
        public ?OneOfCycleTestA $z = null,
    ) {}
}

final readonly class OneOfCycleTestNode
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
    ) {}
}

final readonly class OneOfCycleTestHolder
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([OneOfCycleTestBackRef::class])]
        public ?OneOfCycleTestBackRef $choice = null,
    ) {}
}

final readonly class OneOfCycleTestBackRef
{
    public function __construct(
        #[Reflection\Field(2, new Reflection\ObjectT(OneOfCycleTestHolder::class))]
        public ?OneOfCycleTestHolder $parent = null,
    ) {}
}
