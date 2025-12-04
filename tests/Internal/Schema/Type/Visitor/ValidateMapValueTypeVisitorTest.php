<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Schema\Type;

#[CoversClass(ValidateMapValueTypeVisitor::class)]
final class ValidateMapValueTypeVisitorTest extends TestCase
{
    /**
     * @param Type<*> $type
     * @param non-empty-string $exceptionMessage
     */
    #[TestWith([new Type\ListT(Type\StringT::T), 'Value in map fields cannot be list.'])]
    #[TestWith([new Type\MapT(Type\StringT::T, Type\Int32T::T), 'Value in map fields cannot be map.'])]
    public function testValueTypeNotAllowed(Type $type, string $exceptionMessage): void
    {
        self::expectException(\UnexpectedValueException::class);
        self::expectExceptionMessage($exceptionMessage);
        $type->accept(new ValidateMapValueTypeVisitor());
    }

    /**
     * @param Type<*> $type
     */
    #[DoesNotPerformAssertions]
    #[TestWith([Type\BoolT::T])]
    #[TestWith([Type\StringT::T])]
    #[TestWith([Type\Fixed32T::T])]
    #[TestWith([Type\Fixed64T::T])]
    #[TestWith([Type\Int32T::T])]
    #[TestWith([Type\Int64T::T])]
    #[TestWith([Type\SFixed32T::T])]
    #[TestWith([Type\SFixed64T::T])]
    #[TestWith([Type\SInt32T::T])]
    #[TestWith([Type\SInt64T::T])]
    #[TestWith([Type\Uint32T::T])]
    #[TestWith([Type\Uint64T::T])]
    #[TestWith([new Type\EnumT(TestMapValueEnum::class)])]
    #[TestWith([new Type\MessageT([])])]
    public function testValueTypeAllowed(Type $type): void
    {
        $type->accept(new ValidateMapValueTypeVisitor());
    }
}

/**
 * @internal
 */
enum TestMapValueEnum: int {}
