<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;

#[CoversClass(ToProtobufValueTypeVisitor::class)]
final class MapEncodingTest extends TestCase
{
    public function testEncodingLeavesTheMapOfObjectsIntact(): void
    {
        $encoder = Encoder\Builder::buildDefault();

        $inner = new MapEncodingTestInner(5);
        $message = new MapEncodingTestWithMap(Map::fromArray(['a' => $inner]));

        $first = $encoder->encode($message);
        $second = $encoder->encode($message);

        self::assertSame('0a070a016112020805', bin2hex($first));
        self::assertSame($first, $second);
        self::assertSame($inner, $message->values['a']);
    }

    public function testMapOfObjectsRoundTrips(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new MapEncodingTestWithMap(Map::fromArray([
            'a' => new MapEncodingTestInner(5),
            'b' => new MapEncodingTestInner(7),
        ])));

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapEncodingTestWithMap::class);

        self::assertEquals(new MapEncodingTestInner(5), $decoded->values['a']);
        self::assertEquals(new MapEncodingTestInner(7), $decoded->values['b']);
        self::assertCount(2, $decoded->values);
    }
}

final readonly class MapEncodingTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final readonly class MapEncodingTestWithMap
{
    /**
     * @param Map<string, MapEncodingTestInner> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(MapEncodingTestInner::class)))]
        public Map $values = new Map(),
    ) {}
}
