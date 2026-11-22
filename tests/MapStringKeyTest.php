<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\MapCodec;

#[CoversClass(Serializer::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(MapCodec::class)]
final class MapStringKeyTest extends TestCase
{
    /**
     * @param Map<array-key, int> $map
     */
    #[DataProvider('provideAnIntKeyOfAStringMapIsWrittenAsItsDecimalStringCases')]
    public function testAnIntKeyOfAStringMapIsWrittenAsItsDecimalString(Map $map, string $hex): void
    {
        $bytes = new Serializer()->serialize(message(fieldOf(1, mapOf(stringT, int32T, $map))));

        self::assertSame($hex, bin2hex($bytes));
    }

    /**
     * @return iterable<string, array{Map<array-key, int>, string}>
     */
    public static function provideAnIntKeyOfAStringMapIsWrittenAsItsDecimalStringCases(): iterable
    {
        yield 'a numeric string key from an array' => [Map::fromArray(['1' => 1]), '0a050a01311001'];
        yield 'a negative numeric string key from an array' => [Map::fromArray(['-1' => 1]), '0a060a022d311001'];
        yield 'numeric and non-numeric string keys from an array' => [
            Map::fromArray(['1' => 1, 'a' => 2]),
            '0a050a013110010a050a01611002',
        ];
        yield 'an int key set by hand' => [new Map(new KVPair(7, 1)), '0a050a01371001'];
    }

    public function testANumericStringKeyIsDecodedAsAString(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, int32T))),
            // The entries "1" => 1 and "20" => 2, as written for Map::fromArray(['1' => 1, '20' => 2]).
            (string) hex2bin('0a050a013110010a060a0232301002'),
        );

        $map = $message->fields[1]->value->value ?? null;
        self::assertInstanceOf(Map::class, $map);

        $entries = [];

        foreach ($map as $key => $value) {
            $entries[] = [$key, $value];
        }

        self::assertSame([['1', 1], ['20', 2]], $entries);
    }

    public function testAnObjectWithANumericStringKeyRoundTrips(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new MapStringKeyTestWithMap(Map::fromArray(['1' => 5])));

        self::assertSame('0a050a01311005', bin2hex($bytes));

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, MapStringKeyTestWithMap::class);

        self::assertSame(5, $decoded->values['1']);
        self::assertCount(1, $decoded->values);
    }
}

final readonly class MapStringKeyTestWithMap
{
    /**
     * @param Map<array-key, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public Map $values = new Map(),
    ) {}
}
