<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;

#[CoversClass(Type\MapT::class)]
#[CoversClass(Reflection\MapT::class)]
#[CoversFunction('Thesis\Protobuf\mapT')]
#[CoversFunction('Thesis\Protobuf\mapOf')]
#[CoversClass(Internal\Serde\FieldCodecs::class)]
final class MapDeclarationTest extends TestCase
{
    /**
     * @param \Closure(): mixed $declare
     */
    #[DataProvider('provideOnlyIntegerBoolAndStringKeysAreAllowedCases')]
    public function testOnlyIntegerBoolAndStringKeysAreAllowed(\Closure $declare, string $keyType): void
    {
        $this->expectExceptionObject(new InvalidSchema(\sprintf(
            'Map keys of type "%s" are not allowed: only integer, bool and string keys are.',
            $keyType,
        )));

        $declare();
    }

    /**
     * @return iterable<string, array{\Closure(): mixed, class-string}>
     */
    public static function provideOnlyIntegerBoolAndStringKeysAreAllowedCases(): iterable
    {
        // Not allowed by the phpdoc types either: checked at runtime for callers without static analysis.
        /** @phpstan-ignore argument.type */
        yield 'float' => [static fn(): Type\MapT => mapT(floatT, int32T), Type\FloatT::class];
        /** @phpstan-ignore argument.type */
        yield 'double' => [static fn(): Type\MapT => mapT(doubleT, int32T), Type\DoubleT::class];
        /** @phpstan-ignore argument.type */
        yield 'bytes' => [static fn(): Type\MapT => mapT(bytesT, int32T), Type\BytesT::class];
        /** @phpstan-ignore argument.type */
        yield 'enum' => [static fn(): Type\MapT => mapT(enumT(MapDeclarationTestColor::class), int32T), Type\EnumT::class];
        /** @phpstan-ignore argument.type */
        yield 'message' => [static fn(): Type\MapT => mapT(messageT(), int32T), Type\MessageT::class];
        /** @phpstan-ignore argument.type */
        yield 'recursive message' => [static fn(): Type\MapT => mapT(recursionT(static fn(): Type\MessageT => messageT()), int32T), Type\RecursionT::class];
        /** @phpstan-ignore argument.type */
        yield 'list' => [static fn(): Type\MapT => mapT(listT(int32T), int32T), Type\ListT::class];
        /** @phpstan-ignore argument.type */
        yield 'map' => [static fn(): Type\MapT => mapT(mapT(int32T, int32T), int32T), Type\MapT::class];
        /** @phpstan-ignore argument.type */
        yield 'a value of a bytes key' => [static fn(): Value => mapOf(bytesT, int32T, new Map()), Type\BytesT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection float' => [static fn(): Reflection\MapT => new Reflection\MapT(Reflection\FloatT::T, Reflection\Int32T::T), Reflection\FloatT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection double' => [static fn(): Reflection\MapT => new Reflection\MapT(Reflection\DoubleT::T, Reflection\Int32T::T), Reflection\DoubleT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection bytes' => [static fn(): Reflection\MapT => new Reflection\MapT(Reflection\BytesT::T, Reflection\Int32T::T), Reflection\BytesT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection enum' => [static fn(): Reflection\MapT => new Reflection\MapT(new Reflection\EnumT(MapDeclarationTestColor::class), Reflection\Int32T::T), Reflection\EnumT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection object' => [static fn(): Reflection\MapT => new Reflection\MapT(new Reflection\ObjectT(MapDeclarationTestEntry::class), Reflection\Int32T::T), Reflection\ObjectT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection list' => [static fn(): Reflection\MapT => new Reflection\MapT(new Reflection\ListT(Reflection\Int32T::T), Reflection\Int32T::T), Reflection\ListT::class];
        /** @phpstan-ignore argument.type */
        yield 'reflection map' => [static fn(): Reflection\MapT => new Reflection\MapT(new Reflection\MapT(Reflection\Int32T::T, Reflection\Int32T::T), Reflection\Int32T::T), Reflection\MapT::class];
    }

    /**
     * @param \Closure(): mixed $declare
     */
    #[DataProvider('provideListsAndMapsCannotBeValuesCases')]
    public function testListsAndMapsCannotBeValues(\Closure $declare): void
    {
        $this->expectExceptionObject(new InvalidSchema('Map values cannot be lists or maps: protobuf has no such fields.'));

        $declare();
    }

    /**
     * @return iterable<string, array{\Closure(): mixed}>
     */
    public static function provideListsAndMapsCannotBeValuesCases(): iterable
    {
        /** @phpstan-ignore argument.type */
        yield 'list' => [static fn(): Type\MapT => mapT(int32T, listT(int32T))];
        /** @phpstan-ignore argument.type */
        yield 'map' => [static fn(): Type\MapT => mapT(int32T, mapT(int32T, int32T))];
        /** @phpstan-ignore argument.type */
        yield 'reflection list' => [static fn(): Reflection\MapT => new Reflection\MapT(Reflection\Int32T::T, new Reflection\ListT(Reflection\Int32T::T))];
        /** @phpstan-ignore argument.type */
        yield 'reflection map' => [static fn(): Reflection\MapT => new Reflection\MapT(Reflection\Int32T::T, new Reflection\MapT(Reflection\Int32T::T, Reflection\Int32T::T))];
    }

    /**
     * @param \Closure(): mixed $declare
     */
    #[DataProvider('provideAllowedKeysAndValuesCases')]
    public function testAllowedKeysAndValues(\Closure $declare): void
    {
        self::assertIsObject($declare());
    }

    /**
     * @return iterable<string, array{\Closure(): mixed}>
     */
    public static function provideAllowedKeysAndValuesCases(): iterable
    {
        foreach ([int32T, int64T, uint32T, uint64T, sint32T, sint64T, fixed32T, fixed64T, sfixed32T, sfixed64T, boolT, stringT] as $keyT) {
            yield $keyT::class . ' key' => [static fn(): Type\MapT => mapT($keyT, int32T)];
        }

        $reflectionKeys = [
            Reflection\Int32T::T,
            Reflection\Int64T::T,
            Reflection\Uint32T::T,
            Reflection\Uint64T::T,
            Reflection\SInt32T::T,
            Reflection\SInt64T::T,
            Reflection\Fixed32T::T,
            Reflection\Fixed64T::T,
            Reflection\SFixed32T::T,
            Reflection\SFixed64T::T,
            Reflection\BoolT::T,
            Reflection\StringT::T,
        ];

        foreach ($reflectionKeys as $keyT) {
            yield $keyT::class . ' key' => [static fn(): Reflection\MapT => new Reflection\MapT($keyT, Reflection\Int32T::T)];
        }

        // The type a recursive message refers to may still be under construction: the check does not resolve it.
        yield 'recursive message value' => [static fn(): Type\MapT => mapT(stringT, recursionT(
            static fn(): Type\MessageT => throw new \LogicException('A recursive value must not be resolved.'),
        ))];
    }

    public function testARecursiveMapValueOfAReflectedClassRoundTrips(): void
    {
        $tree = new MapDeclarationTestEntry(['a' => new MapDeclarationTestEntry(['b' => new MapDeclarationTestEntry()])]);

        $bytes = Encoder\Builder::buildDefault()->encode($tree);

        self::assertEquals($tree, Decoder\Builder::buildDefault()->decode($bytes, MapDeclarationTestEntry::class));
    }
}

enum MapDeclarationTestColor: int
{
    case UNSPECIFIED = 0;
}

final readonly class MapDeclarationTestEntry
{
    /**
     * @param array<string, self> $children
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(self::class)))]
        public array $children = [],
    ) {}
}
