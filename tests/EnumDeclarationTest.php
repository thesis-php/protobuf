<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;

#[CoversClass(Type\EnumT::class)]
#[CoversClass(Reflection\EnumT::class)]
final class EnumDeclarationTest extends TestCase
{
    /**
     * @param \Closure(): mixed $declare
     */
    #[DataProvider('provideOnlyIntBackedEnumsAreAcceptedCases')]
    public function testOnlyIntBackedEnumsAreAccepted(\Closure $declare, string $message): void
    {
        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessage($message);

        $declare();
    }

    /**
     * @return iterable<string, array{\Closure(): mixed, string}>
     */
    public static function provideOnlyIntBackedEnumsAreAcceptedCases(): iterable
    {
        $stringBacked = \sprintf('"%s" must be an int-backed enum', EnumDeclarationTestString::class);
        $pure = \sprintf('"%s" must be an int-backed enum', EnumDeclarationTestPure::class);
        $class = \sprintf('"%s" must be an int-backed enum', \stdClass::class);

        yield 'string-backed' => [static fn() => enumT(EnumDeclarationTestString::class), $stringBacked];
        yield 'string-backed value' => [static fn() => enumOf(EnumDeclarationTestString::A), $stringBacked];
        /** @phpstan-ignore argument.type */
        yield 'pure enum' => [static fn() => enumT(EnumDeclarationTestPure::class), $pure];
        /** @phpstan-ignore argument.type */
        yield 'not an enum' => [static fn() => enumT(\stdClass::class), $class];
        yield 'reflection string-backed' => [static fn() => new Reflection\EnumT(EnumDeclarationTestString::class), $stringBacked];
        /** @phpstan-ignore argument.type */
        yield 'reflection pure enum' => [static fn() => new Reflection\EnumT(EnumDeclarationTestPure::class), $pure];
    }

    public function testIntBackedEnumsAreAccepted(): void
    {
        self::assertSame(EnumDeclarationTestInt::class, enumT(EnumDeclarationTestInt::class)->enum);
        self::assertSame(EnumDeclarationTestInt::class, new Reflection\EnumT(EnumDeclarationTestInt::class)->enum);
    }
}

enum EnumDeclarationTestInt: int
{
    case A = 1;
}

enum EnumDeclarationTestString: string
{
    case A = 'a';
}

enum EnumDeclarationTestPure
{
    case A;
}
