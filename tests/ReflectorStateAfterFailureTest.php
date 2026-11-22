<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Reflector;

/**
 * A class whose reflection failed part-way is not in progress afterwards: what is reflected next fails or succeeds as
 * it does with a fresh reflector.
 */
#[CoversClass(Reflector::class)]
final class ReflectorStateAfterFailureTest extends TestCase
{
    /**
     * @param class-string $failing
     * @param class-string $referring
     */
    #[DataProvider('provideFailedClassCases')]
    public function testAClassThatRefersToAFailedClassFailsAsWithAFreshReflector(string $failing, string $referring): void
    {
        $reflector = Reflector::build();

        self::assertEquals(
            self::failure(static fn() => Reflector::build()->type($failing)),
            self::failure(static fn() => $reflector->type($failing)),
        );
        self::assertEquals(
            self::failure(static fn() => Reflector::build()->type($referring)),
            self::failure(static fn() => $reflector->type($referring)),
        );
        self::assertEquals(
            self::failure(static fn() => Reflector::build()->type($failing)),
            self::failure(static fn() => $reflector->type($failing)),
        );
    }

    /**
     * @param class-string $failing
     * @param class-string $referring
     */
    #[DataProvider('provideFailedClassCases')]
    public function testAClassThatRefersToAFailedClassFailsAsWithAFreshDecoder(string $failing, string $referring): void
    {
        $decoder = Decoder\Builder::buildDefault();

        self::assertEquals(
            self::failure(static fn() => Decoder\Builder::buildDefault()->decode('', $failing)),
            self::failure(static fn() => $decoder->decode('', $failing)),
        );
        self::assertEquals(
            self::failure(static fn() => Decoder\Builder::buildDefault()->decode('', $referring)),
            self::failure(static fn() => $decoder->decode('', $referring)),
        );
    }

    /**
     * @param class-string $failing
     * @param class-string $referring
     */
    #[DataProvider('provideFailedClassCases')]
    public function testAClassThatRefersToAFailedClassFailsAsWithAFreshEncoder(string $failing, string $referring): void
    {
        $encoder = Encoder\Builder::buildDefault();

        // Without a variant set, a oneof does not need the type of its variants: such an object is encoded.
        self::assertEquals(
            self::outcome(static fn() => Encoder\Builder::buildDefault()->encode(new $failing())),
            self::outcome(static fn() => $encoder->encode(new $failing())),
        );
        self::assertEquals(
            self::failure(static fn() => Encoder\Builder::buildDefault()->encode(new $referring())),
            self::failure(static fn() => $encoder->encode(new $referring())),
        );
    }

    /**
     * @return iterable<string, array{class-string, class-string}>
     */
    public static function provideFailedClassCases(): iterable
    {
        yield 'a message field of a class with an invalid property' => [
            ReflectorStateAfterFailureTestOuter::class,
            ReflectorStateAfterFailureTestReferrer::class,
        ];

        yield 'a message field of a class that declares a field number twice' => [
            ReflectorStateAfterFailureTestDuplicateOuter::class,
            ReflectorStateAfterFailureTestDuplicateReferrer::class,
        ];

        yield 'a oneof variant with a message field of a class with an invalid property' => [
            ReflectorStateAfterFailureTestHolder::class,
            ReflectorStateAfterFailureTestVariantReferrer::class,
        ];

        yield 'a message field that refers back to the class and to a class with an invalid property' => [
            ReflectorStateAfterFailureTestCycleStart::class,
            ReflectorStateAfterFailureTestCycleReferrer::class,
        ];

        yield 'a message field of a class whose type was built in a cycle with a class with an invalid property' => [
            ReflectorStateAfterFailureTestCycleNext::class,
            ReflectorStateAfterFailureTestCycleStartReferrer::class,
        ];
    }

    /**
     * @param \Closure(): mixed $run
     * @return mixed what the closure returns or throws
     */
    private static function outcome(\Closure $run): mixed
    {
        try {
            return $run();
        } catch (\Throwable $e) {
            return $e;
        }
    }

    /**
     * @param \Closure(): mixed $reflect
     */
    private static function failure(\Closure $reflect): \Throwable
    {
        try {
            $reflect();
        } catch (\Throwable $e) {
            return $e;
        }

        self::fail('Nothing was thrown.');
    }
}

final readonly class ReflectorStateAfterFailureTestInvalid
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public string $value = '',
    ) {}
}

final readonly class ReflectorStateAfterFailureTestOuter
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestInvalid::class))]
        public ?ReflectorStateAfterFailureTestInvalid $invalid = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestReferrer
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestOuter::class))]
        public ?ReflectorStateAfterFailureTestOuter $outer = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestDuplicate
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $first = 0,
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $second = 0,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestDuplicateOuter
{
    /**
     * @param list<ReflectorStateAfterFailureTestDuplicate> $duplicates
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(ReflectorStateAfterFailureTestDuplicate::class)))]
        public array $duplicates = [],
    ) {}
}

final readonly class ReflectorStateAfterFailureTestDuplicateReferrer
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestDuplicateOuter::class))]
        public ?ReflectorStateAfterFailureTestDuplicateOuter $outer = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestVariant
{
    public function __construct(
        #[Reflection\Field(2, new Reflection\ObjectT(ReflectorStateAfterFailureTestInvalid::class))]
        public ?ReflectorStateAfterFailureTestInvalid $invalid = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestHolder
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([ReflectorStateAfterFailureTestVariant::class])]
        public ?ReflectorStateAfterFailureTestVariant $choice = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestVariantReferrer
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestVariant::class))]
        public ?ReflectorStateAfterFailureTestVariant $variant = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestCycleStart
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestCycleNext::class))]
        public ?ReflectorStateAfterFailureTestCycleNext $next = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestCycleNext
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestCycleStart::class))]
        public ?ReflectorStateAfterFailureTestCycleStart $start = null,
        #[Reflection\Field(2, new Reflection\ObjectT(ReflectorStateAfterFailureTestInvalid::class))]
        public ?ReflectorStateAfterFailureTestInvalid $invalid = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestCycleReferrer
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestCycleNext::class))]
        public ?ReflectorStateAfterFailureTestCycleNext $next = null,
    ) {}
}

final readonly class ReflectorStateAfterFailureTestCycleStartReferrer
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ReflectorStateAfterFailureTestCycleStart::class))]
        public ?ReflectorStateAfterFailureTestCycleStart $start = null,
    ) {}
}
