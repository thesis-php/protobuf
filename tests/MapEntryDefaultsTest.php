<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\ZeroValue;

#[CoversClass(Serializer::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(MapCodec::class)]
#[CoversClass(ZeroValue::class)]
final class MapEntryDefaultsTest extends TestCase
{
    /**
     * @param Type<mixed, *, 'indexed'> $keyT
     * @param Type<mixed, *, *, 'map-value'> $valueT
     * @param list<array{mixed, mixed}> $expected
     */
    #[DataProvider('provideAMissingKeyOrValueIsTheZeroValueOfItsTypeCases')]
    public function testAMissingKeyOrValueIsTheZeroValueOfItsType(Type $keyT, Type $valueT, string $hex, array $expected): void
    {
        $message = new Serializer()->deserialize(messageT(fieldT(1, mapT($keyT, $valueT))), (string) hex2bin($hex));

        self::assertEquals($expected, self::entries($message));
        self::assertSame([], $message->unknowns);
    }

    /**
     * @return iterable<string, array{Type<mixed, *, 'indexed'>, Type<mixed, *, *, 'map-value'>, string, list<array{mixed, mixed}>}>
     */
    public static function provideAMissingKeyOrValueIsTheZeroValueOfItsTypeCases(): iterable
    {
        yield 'no value' => [stringT, int32T, '0a030a0161', [['a', 0]]];
        yield 'no key' => [stringT, int32T, '0a021005', [['', 5]]];
        yield 'an empty entry' => [stringT, int32T, '0a00', [['', 0]]];
        yield 'an int key' => [int64T, stringT, '0a03120161', [[0, 'a']]];
        yield 'a bool key' => [boolT, stringT, '0a03120161', [[false, 'a']]];
        yield 'a uint64 key' => [uint64T, stringT, '0a03120161', [[new Number(0), 'a']]];
        yield 'a fixed64 key' => [fixed64T, stringT, '0a03120161', [[new Number(0), 'a']]];
        yield 'a string value' => [int32T, stringT, '0a020801', [[1, '']]];
        yield 'a bytes value' => [int32T, bytesT, '0a020801', [[1, '']]];
        yield 'a float value' => [int32T, floatT, '0a020801', [[1, 0.0]]];
        yield 'a double value' => [int32T, doubleT, '0a020801', [[1, 0.0]]];
        yield 'a uint64 value' => [int32T, uint64T, '0a020801', [[1, new Number(0)]]];
        yield 'a message value' => [int32T, messageT(fieldT(1, int32T)), '0a020801', [[1, new Message()]]];
        yield 'an enum value' => [int32T, enumT(MapEntryDefaultsTestColor::class), '0a020801', [[1, MapEntryDefaultsTestColor::UNSPECIFIED]]];
        yield 'an enum value without a case for 0' => [int32T, enumT(MapEntryDefaultsTestSize::class), '0a020801', [[1, MapEntryDefaultsTestSize::SMALL]]];
    }

    public function testAnEntryWithoutAValueOfAnEnumWithoutCasesIsAnUnknownField(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, enumT(MapEntryDefaultsTestNothing::class))), fieldT(2, int32T)),
            // The entry holds key "a" and an undeclared field 3.
            (string) hex2bin('0a050a016118011007'),
        );

        self::assertSame([], self::entries($message));
        self::assertEquals([new UnknownFields\UnknownField(new Tag(1, WireType::BYTES), "\x0a\x01a\x18\x01")], $message->unknowns);
    }

    /**
     * @param list<array{mixed, mixed}> $expected
     * @param string $unknown the entry as it was written, or none if the entry is in the map
     */
    #[DataProvider('provideAnEntryWithAnUnknownEnumValueIsAnUnknownFieldCases')]
    public function testAnEntryWithAnUnknownEnumValueIsAnUnknownField(string $hex, array $expected, string $unknown): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, mapT(stringT, enumT(MapEntryDefaultsTestColor::class)))),
            (string) hex2bin($hex),
        );

        self::assertSame($expected, self::entries($message));
        self::assertEquals(
            $unknown === '' ? [] : [new UnknownFields\UnknownField(new Tag(1, WireType::BYTES), (string) hex2bin($unknown))],
            $message->unknowns,
        );
    }

    /**
     * @return iterable<string, array{string, list<array{mixed, mixed}>, string}>
     */
    public static function provideAnEntryWithAnUnknownEnumValueIsAnUnknownFieldCases(): iterable
    {
        yield 'among known entries' => ['0a050a016110010a050a016210440a050a01631002', [['a', MapEntryDefaultsTestColor::RED], ['c', MapEntryDefaultsTestColor::BLUE]], '0a01621044'];
        yield 'without a key' => ['0a021044', [], '1044'];
        yield 'a negative number' => ['0a0e0a016110fbffffffffffffffff01', [], '0a016110fbffffffffffffffff01'];
        yield 'after a known value' => ['0a070a016110011044', [], '0a016110011044'];
        yield 'with an undeclared field' => ['0a070a016118441044', [], '0a016118441044'];
        yield 'with a repeated key' => ['0a080a01620a01611044', [], '0a01620a01611044'];
        yield 'before a known value' => ['0a070a016110441001', [['a', MapEntryDefaultsTestColor::RED]], ''];
    }

    public function testDecoderFillsInAMissingKeyAndValue(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0a030a01610a02120012021003'),
            MapEntryDefaultsTestMessage::class,
        );

        self::assertEquals(
            new Map(new KVPair('a', new MapEntryDefaultsTestInner()), new KVPair('', new MapEntryDefaultsTestInner())),
            $decoded->inners,
        );
        self::assertEquals(new Map(new KVPair(0, 3)), $decoded->numbers);
    }

    /**
     * @return list<array{mixed, mixed}>
     */
    private static function entries(Message $message): array
    {
        $map = ($message->fields[1] ?? null)?->value->value;
        self::assertInstanceOf(Map::class, $map);

        $entries = [];

        foreach ($map as $key => $value) {
            $entries[] = [$key, $value];
        }

        return $entries;
    }
}

enum MapEntryDefaultsTestColor: int
{
    case NEGATIVE = -1;
    case UNSPECIFIED = 0;
    case RED = 1;
    case BLUE = 2;
}

enum MapEntryDefaultsTestSize: int
{
    case SMALL = 1;
    case LARGE = 2;
}

enum MapEntryDefaultsTestNothing: int {}

final readonly class MapEntryDefaultsTestMessage
{
    /**
     * @param Map<string, MapEntryDefaultsTestInner> $inners
     * @param Map<int, int> $numbers
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(MapEntryDefaultsTestInner::class)))]
        public Map $inners = new Map(),
        #[Reflection\Field(2, new Reflection\MapT(Reflection\Int32T::T, Reflection\Int32T::T))]
        public Map $numbers = new Map(),
    ) {}
}

final readonly class MapEntryDefaultsTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 7,
    ) {}
}
