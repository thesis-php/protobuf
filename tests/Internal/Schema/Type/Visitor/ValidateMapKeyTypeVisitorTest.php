<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Schema\Type;

#[CoversClass(ValidateMapKeyTypeVisitor::class)]
final class ValidateMapKeyTypeVisitorTest extends TestCase
{
    /**
     * @param Type<*> $type
     * @param non-empty-string $exceptionMessage
     */
    #[TestWith([Type\FloatT::T, 'Key in map fields cannot be float.'])]
    #[TestWith([Type\DoubleT::T, 'Key in map fields cannot be double.'])]
    #[TestWith([new Type\ListT(Type\StringT::T), 'Key in map fields cannot be list.'])]
    #[TestWith([new Type\MapT(Type\StringT::T, Type\Fixed64T::T), 'Key in map fields cannot be map.'])]
    #[TestWith([new Type\EnumT(TestMapKeyEnum::class), 'Key in map fields cannot be enum.'])]
    #[TestWith([new Type\MessageT(), 'Key in map fields cannot be message.'])]
    public function testKeyTypeNotAllowed(Type $type, string $exceptionMessage): void
    {
        self::expectException(\UnexpectedValueException::class);
        self::expectExceptionMessage($exceptionMessage);
        $type->accept(new ValidateMapKeyTypeVisitor());
    }

    /**
     * @param Type<*> $type
     */
    #[DoesNotPerformAssertions]
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
    public function testKeyTypeAllowed(Type $type): void
    {
        $type->accept(new ValidateMapKeyTypeVisitor());
    }
}

/**
 * @internal
 */
enum TestMapKeyEnum: int {}
