<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Exception\MappingError;
use Thesis\Protobuf\Reflection\Exception\PropertyRequired;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(ClassReflector::class)]
#[CoversClass(Reflector::class)]
final class PropertyDefaultTest extends TestCase
{
    public function testAPropertyWithoutADefaultThatCannotBeNullIsRequired(): void
    {
        try {
            Reflector::build()->map(message(), PropertyDefaultTestMessage::class);
            self::fail('Mapping must fail.');
        } catch (MappingError $e) {
            $required = array_map(
                static fn(\Throwable $reason): string => $reason instanceof PropertyRequired ? $reason->property : $reason::class,
                $e->reasons,
            );
            sort($required);

            // An untyped property is null by default, even a promoted one.
            self::assertSame(['promotedRequired', 'required'], $required);
        }
    }

    public function testMissingFieldsGetTheDefaultsOfTheirProperties(): void
    {
        $object = Reflector::build()->map(
            message(fieldOf(2, int32Of(1)), fieldOf(7, int32Of(3))),
            PropertyDefaultTestMessage::class,
        );

        self::assertSame(5, $object->promotedWithDefault);
        self::assertSame(1, $object->promotedRequired);
        self::assertNull($object->promotedUntyped);
        self::assertSame(7, $object->withDefault);
        self::assertNull($object->nullable);
        self::assertNull($object->untyped);
        self::assertSame(3, $object->required);
    }
}

final class PropertyDefaultTestMessage
{
    #[Reflection\Field(4, Reflection\Int32T::T)]
    public int $withDefault = 7;

    /** @var ?int */
    #[Reflection\Field(6, Reflection\Int32T::T)]
    public $untyped;

    #[Reflection\Field(7, Reflection\Int32T::T)]
    // @phpstan-ignore property.uninitialized (the decoder sets it)
    public int $required;

    /**
     * @param ?int $promotedUntyped
     */
    public function __construct(
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $promotedRequired,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public $promotedUntyped,
        #[Reflection\Field(5, Reflection\Int32T::T)]
        public ?int $nullable,
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $promotedWithDefault = 5,
    ) {}
}
