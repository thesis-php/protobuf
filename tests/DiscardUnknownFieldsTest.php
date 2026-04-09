<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

#[CoversFunction('\Thesis\Protobuf\Internal\Wire\discardUnknown')]
final class DiscardUnknownFieldsTest extends TestCase
{
    public function testUnknownVarintField(): void
    {
        $this->assertUnknownFieldSkipped(fieldOf(2, int32Of(99)));
    }

    public function testUnknownFixed32Field(): void
    {
        $this->assertUnknownFieldSkipped(fieldOf(2, fixed32Of(42)));
    }

    public function testUnknownFixed64Field(): void
    {
        $this->assertUnknownFieldSkipped(fieldOf(2, fixed64Of(new Number(42))));
    }

    public function testUnknownBytesField(): void
    {
        $this->assertUnknownFieldSkipped(fieldOf(2, stringOf('unknown')));
    }

    /**
     * @param FieldDescriptor<*> $unknownField
     */
    private function assertUnknownFieldSkipped(FieldDescriptor $unknownField): void
    {
        $serializer = new Serializer();

        $bytes = $serializer->serialize(message(
            fieldOf(1, int32Of(42)),
            $unknownField,
            fieldOf(3, stringOf('hello')),
        ));

        $message = $serializer->deserialize(
            messageT(
                fieldT(1, int32T),
                fieldT(3, stringT),
            ),
            $bytes,
        );

        self::assertCount(2, $message);
        self::assertSame(42, $message->fields[1]->value->value); // @phpstan-ignore offsetAccess.notFound
        self::assertSame('hello', $message->fields[3]->value->value); // @phpstan-ignore offsetAccess.notFound
    }
}
