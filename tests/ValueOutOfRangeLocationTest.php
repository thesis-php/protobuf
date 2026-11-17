<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Encoder\Internal\ReflectionEncoder;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Serde\SerializeMessage;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(ValueOutOfRange::class)]
#[CoversClass(Serializer::class)]
#[CoversClass(SerializeMessage::class)]
#[CoversClass(ReflectionEncoder::class)]
#[CoversClass(Reflector::class)]
final class ValueOutOfRangeLocationTest extends TestCase
{
    /**
     * @param list<positive-int> $fields
     */
    #[DataProvider('provideSerializerNamesTheFieldCases')]
    public function testSerializerNamesTheField(Message $message, array $fields, string $location): void
    {
        try {
            new Serializer()->serialize($message);
            self::fail('Serialization must fail.');
        } catch (ValueOutOfRange $e) {
            self::assertSame($fields, $e->fields);
            self::assertSame([], $e->properties);
            self::assertStringContainsString("for uint32 in field {$location}: expected", $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{Message, list<positive-int>, string}>
     */
    public static function provideSerializerNamesTheFieldCases(): iterable
    {
        yield 'top-level field' => [message(fieldOf(3, uint32Of(-5))), [3], '3'];
        yield 'nested message' => [message(fieldOf(3, messageOf(fieldOf(1, uint32Of(-5))))), [3, 1], '3.1'];
        yield 'list element' => [message(fieldOf(4, listOf(uint32T, [1, -5]))), [4], '4'];
        yield 'map key' => [message(fieldOf(5, mapOf(uint32T, stringT, Map::fromArray([-5 => 'a'])))), [5, 1], '5.1'];
        yield 'map value' => [message(fieldOf(5, mapOf(stringT, uint32T, Map::fromArray(['a' => -5])))), [5, 2], '5.2'];
    }

    /**
     * @param list<positive-int> $fields
     * @param list<non-empty-string> $properties
     */
    #[DataProvider('provideEncoderNamesTheClassAndPropertyCases')]
    public function testEncoderNamesTheClassAndProperty(object $message, array $fields, array $properties): void
    {
        try {
            Encoder\Builder::buildDefault()->encode($message);
            self::fail('Encoding must fail.');
        } catch (ValueOutOfRange $e) {
            self::assertSame($fields, $e->fields);
            self::assertSame($properties, $e->properties);
            self::assertStringContainsString(\sprintf('(%s)', implode(' → ', $properties)), $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{object, list<positive-int>, list<non-empty-string>}>
     */
    public static function provideEncoderNamesTheClassAndPropertyCases(): iterable
    {
        $outer = LocationTestOuter::class;
        $inner = LocationTestInner::class;

        yield 'scalar' => [new LocationTestInner(-5), [1], ["{$inner}::\$id"]];
        yield 'nested object' => [new LocationTestOuter(inner: new LocationTestInner(-5)), [3, 1], ["{$outer}::\$inner", "{$inner}::\$id"]];
        yield 'list of objects' => [
            new LocationTestOuter(items: [new LocationTestInner(1), new LocationTestInner(-5)]),
            [4, 1],
            ["{$outer}::\$items", "{$inner}::\$id"],
        ];
        yield 'map value object' => [
            new LocationTestOuter(byName: Map::fromArray(['a' => new LocationTestInner(-5)])),
            [5, 2, 1],
            ["{$outer}::\$byName[value]", "{$inner}::\$id"],
        ];
        yield 'map key' => [
            new LocationTestOuter(byId: Map::fromArray([-5 => 'a'])),
            [6, 1],
            ["{$outer}::\$byId[key]"],
        ];
        yield 'oneof variant' => [
            new LocationTestOuter(choice: new LocationTestVariant(-5)),
            [7],
            ["{$outer}::\$choice", LocationTestVariant::class . '::$count'],
        ];
    }
}

final readonly class LocationTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Uint32T::T)]
        public int $id = 0,
    ) {}
}

final readonly class LocationTestVariant
{
    public function __construct(
        #[Reflection\Field(7, Reflection\Uint32T::T)]
        public int $count = 0,
    ) {}
}

final readonly class LocationTestOuter
{
    /**
     * @param list<LocationTestInner> $items
     * @param Map<string, LocationTestInner> $byName
     * @param Map<int, string> $byId
     */
    public function __construct(
        #[Reflection\Field(3, new Reflection\ObjectT(LocationTestInner::class))]
        public ?LocationTestInner $inner = null,
        #[Reflection\Field(4, new Reflection\ListT(new Reflection\ObjectT(LocationTestInner::class)))]
        public array $items = [],
        #[Reflection\Field(5, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(LocationTestInner::class)))]
        public Map $byName = new Map(),
        #[Reflection\Field(6, new Reflection\MapT(Reflection\Uint32T::T, Reflection\StringT::T))]
        public Map $byId = new Map(),
        #[Reflection\OneOf([LocationTestVariant::class])]
        public ?LocationTestVariant $choice = null,
    ) {}
}
