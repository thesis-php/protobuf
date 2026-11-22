<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;

#[CoversClass(Type\ListT::class)]
#[CoversClass(Reflection\ListT::class)]
final class ListDeclarationTest extends TestCase
{
    /**
     * @param \Closure(): mixed $declare
     */
    #[DataProvider('provideListsAndMapsCannotBeElementsCases')]
    public function testListsAndMapsCannotBeElements(\Closure $declare): void
    {
        $this->expectExceptionObject(new InvalidSchema('Repeated fields cannot hold lists or maps: protobuf has no such fields.'));

        $declare();
    }

    /**
     * @return iterable<string, array{\Closure(): mixed}>
     */
    public static function provideListsAndMapsCannotBeElementsCases(): iterable
    {
        // Not allowed by the phpdoc types either: checked at runtime for callers without static analysis.
        /** @phpstan-ignore argument.type */
        yield 'list of lists' => [static fn(): Type\ListT => listT(listT(int32T))];
        /** @phpstan-ignore argument.type */
        yield 'list of maps' => [static fn(): Type\ListT => listT(mapT(stringT, int32T))];
        /** @phpstan-ignore argument.type */
        yield 'reflection list of lists' => [static fn(): Reflection\ListT => new Reflection\ListT(new Reflection\ListT(Reflection\Int32T::T))];
        /** @phpstan-ignore argument.type */
        yield 'reflection list of maps' => [static fn(): Reflection\ListT => new Reflection\ListT(new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))];
    }
}
