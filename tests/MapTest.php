<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Map::class)]
#[CoversClass(KVPair::class)]
#[CoversClass(Exception\IllegalProtobufKeyMap::class)]
#[CoversClass(Exception\KeyIsNotDefined::class)]
final class MapTest extends TestCase
{
    #[DataProvider('provideKeyCases')]
    public function testItHoldsAKey(mixed $key): void
    {
        /** @var Map<mixed, string> $map */
        $map = new Map();
        $map[$key] = 'value';

        self::assertTrue(isset($map[$key]));
        self::assertSame('value', $map[$key]);
        self::assertCount(1, $map);
        self::assertSame([[$key, 'value']], self::entries($map));
    }

    #[DataProvider('provideKeyCases')]
    public function testUnsettingAKeyRemovesIt(mixed $key): void
    {
        /** @var Map<mixed, string> $map */
        $map = new Map();
        $map[$key] = 'value';
        $map['other'] = 'other';

        unset($map[$key]);

        self::assertFalse(isset($map[$key]));
        self::assertCount(1, $map);
        self::assertSame([['other', 'other']], self::entries($map));
    }

    #[DataProvider('provideKeyCases')]
    public function testAKeyIsMissingFromAnEmptyMap(mixed $key): void
    {
        $map = new Map();

        self::assertFalse(isset($map[$key]));
        self::assertCount(0, $map);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideKeyCases(): iterable
    {
        yield 'zero' => [0];
        yield 'a positive int' => [1];
        yield 'a negative int' => [-1];
        yield 'the smallest int' => [PHP_INT_MIN];
        yield 'the largest int' => [PHP_INT_MAX];
        yield 'an empty string' => [''];
        yield 'a string' => ['key'];
        yield 'a string with double quotes' => ['"quoted"'];
        yield 'a string with a backtick' => ['`'];
        yield 'a string with backticks around it' => ['`key`'];
        yield 'a string with a backslash' => ['back\slash'];
        yield 'a string with a backslash before a backtick' => ['\`'];
        yield 'a string with NUL bytes' => ["\0nul\0"];
        yield 'a numeric string' => ['1'];
        yield 'a numeric string with a leading zero' => ['01'];
        yield 'a decimal string' => ['1.0'];
        yield 'a string of a negative zero' => ['-0'];
        yield 'a multi-byte string' => ['ключ'];
        yield 'the string "true"' => ['true'];
        yield 'the string "t"' => ['t'];
        yield 'the string "f"' => ['f'];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'a Number of zero' => [new Number(0)];
        yield 'a Number of one' => [new Number(1)];
        yield 'a Number of one with a scale' => [new Number('1.0')];
        yield 'a Number of the largest int' => [new Number(PHP_INT_MAX)];
        yield 'a Number above the largest int' => [new Number('9223372036854775808')];
        yield 'a Number of the largest uint64' => [new Number('18446744073709551615')];
    }

    /**
     * @param non-empty-string $message
     */
    #[DataProvider('provideGettingAMissingKeyThrowsCases')]
    public function testGettingAMissingKeyThrows(mixed $key, string $message): void
    {
        /** @var Map<mixed, string> $map */
        $map = new Map(new KVPair('other', 'other'));

        $this->expectExceptionObject(new Exception\KeyIsNotDefined($message));

        $map[$key];
    }

    /**
     * @return iterable<string, array{mixed, non-empty-string}>
     */
    public static function provideGettingAMissingKeyThrowsCases(): iterable
    {
        yield 'an int' => [-7, '-7'];
        yield 'a string' => ['key', '"key"'];
        yield 'an empty string' => ['', '""'];
        yield 'a string with double quotes' => ['say "hi"', '"say \"hi\""'];
        yield 'true' => [true, 'true'];
        yield 'false' => [false, 'false'];
        yield 'a Number' => [new Number('18446744073709551615'), '18446744073709551615'];
    }

    public function testGettingAMissingKeyNamesItInTheMessage(): void
    {
        $map = new Map();

        $this->expectException(Exception\KeyIsNotDefined::class);
        $this->expectExceptionMessage('Key ""a\"b"" is not defined in the protobuf map.');

        $map['a"b'];
    }

    public function testUnsettingAMissingKeyDoesNothing(): void
    {
        /** @var Map<string, int> $map */
        $map = new Map(new KVPair('a', 1));

        unset($map['b']);

        self::assertSame([['a', 1]], self::entries($map));
    }

    public function testSettingAKeyAgainReplacesTheValueInPlace(): void
    {
        /** @var Map<string, int> $map */
        $map = new Map();
        $map['a'] = 1;
        $map['b'] = 2;
        $map['c'] = 3;

        $map['a'] = 10;

        self::assertSame(10, $map['a']);
        self::assertSame([['a', 10], ['b', 2], ['c', 3]], self::entries($map));
    }

    public function testAKeySetAgainAfterItIsUnsetGoesLast(): void
    {
        /** @var Map<string, int> $map */
        $map = new Map(new KVPair('a', 1), new KVPair('b', 2));

        unset($map['a']);
        $map['a'] = 3;

        self::assertSame([['b', 2], ['a', 3]], self::entries($map));
    }

    public function testIterationKeepsTheInsertionOrderAndTheKeysAsTheyWereSet(): void
    {
        $number = new Number('18446744073709551615');

        /** @var Map<mixed, string> $map */
        $map = new Map();
        $map['b'] = 'string b';
        $map[2] = 'int 2';
        $map['1'] = 'string 1';
        $map[true] = 'true';
        $map[$number] = 'number';
        $map[false] = 'false';
        $map[1] = 'int 1';
        $map[''] = 'empty string';

        self::assertCount(8, $map);
        self::assertSame(
            [
                ['b', 'string b'],
                [2, 'int 2'],
                ['1', 'string 1'],
                [true, 'true'],
                [$number, 'number'],
                [false, 'false'],
                [1, 'int 1'],
                ['', 'empty string'],
            ],
            self::entries($map),
        );
    }

    public function testAnEmptyMapHasNoEntries(): void
    {
        $map = new Map();

        self::assertCount(0, $map);
        self::assertSame([], self::entries($map));
    }

    public function testItIsBuiltFromPairsInTheirOrder(): void
    {
        /** @var list<KVPair<mixed, mixed>> $pairs */
        $pairs = [new KVPair('a', 1), new KVPair(2, 'two'), new KVPair(true, null)];
        $map = new Map(...$pairs);

        self::assertCount(3, $map);
        self::assertSame([['a', 1], [2, 'two'], [true, null]], self::entries($map));
    }

    public function testTheLastPairOfAKeyWinsInThePlaceOfTheFirst(): void
    {
        $map = new Map(new KVPair('a', 1), new KVPair('b', 2), new KVPair('a', 3));

        self::assertSame([['a', 3], ['b', 2]], self::entries($map));
    }

    public function testItIsBuiltFromAnArray(): void
    {
        $map = Map::fromArray(['x' => 1, '1' => 2, '01' => 3, '-5' => 4, '1.5' => 5, 7 => 6]);

        // PHP turns the decimal string keys "1" and "-5" into ints, but not "01" or "1.5".
        self::assertSame([['x', 1], [1, 2], ['01', 3], [-5, 4], ['1.5', 5], [7, 6]], self::entries($map));
        self::assertFalse(isset($map['1']));
        self::assertSame(2, $map[1]);
    }

    public function testItIsBuiltFromAnEmptyArray(): void
    {
        $map = Map::fromArray([]);

        self::assertCount(0, $map);
    }

    /**
     * @param \Closure(Map<mixed, mixed>, mixed): mixed $access
     */
    #[DataProvider('provideAnIllegalKeyThrowsCases')]
    public function testAnIllegalKeyThrows(\Closure $access, mixed $key): void
    {
        /** @var Map<mixed, mixed> $map */
        $map = new Map(new KVPair('a', 1));

        try {
            $access($map, $key);
            self::fail('An illegal key was accepted.');
        } catch (Exception\IllegalProtobufKeyMap $e) {
            self::assertSame(new Exception\IllegalProtobufKeyMap($key)->getMessage(), $e->getMessage());
        }

        self::assertSame([['a', 1]], self::entries($map));
    }

    /**
     * @return iterable<string, array{\Closure(Map<mixed, mixed>, mixed): mixed, mixed}>
     */
    public static function provideAnIllegalKeyThrowsCases(): iterable
    {
        $accesses = [
            'isset' => static fn(Map $map, mixed $key): bool => isset($map[$key]),
            'get' => static fn(Map $map, mixed $key): mixed => $map[$key],
            'set' => static function (Map $map, mixed $key): void {
                $map[$key] = 2;
            },
            'unset' => static function (Map $map, mixed $key): void {
                unset($map[$key]);
            },
            'construct' => static fn(Map $map, mixed $key): Map => new Map(new KVPair($key, 2)),
        ];

        $keys = [
            'a float' => 1.0,
            'a float of zero' => 0.0,
            'null' => null,
            'an empty array' => [],
            'an array' => [1],
            'an object' => new \stdClass(),
            'a closure' => static fn(): int => 1,
            'an enum' => MapTestKey::A,
            'a stringable object' => new MapTestStringable(),
        ];

        foreach ($accesses as $accessName => $access) {
            foreach ($keys as $keyName => $key) {
                yield "{$accessName} with {$keyName}" => [$access, $key];
            }
        }
    }

    public function testAppendingThrowsBecauseTheKeyIsNull(): void
    {
        /** @var Map<mixed, int> $map */
        $map = new Map();

        try {
            $map[] = 1;
            self::fail('An entry without a key was accepted.');
        } catch (Exception\IllegalProtobufKeyMap $e) {
            self::assertSame(new Exception\IllegalProtobufKeyMap(null)->getMessage(), $e->getMessage());
        }

        self::assertCount(0, $map);
    }

    public function testAnIllegalKeyNamesItsTypeInTheMessage(): void
    {
        $this->expectException(Exception\IllegalProtobufKeyMap::class);
        $this->expectExceptionMessage(\sprintf('Type "%s" cannot be a key in a protobuf map.', MapTestStringable::class));

        $map = new Map();
        $map[new MapTestStringable()] = 1;
    }

    /**
     * A uint64 or fixed64 key is decoded as a Number, while a user writes a small one as an int: both address the same
     * entry, so that the map decoded can be read with int keys.
     */
    public function testAnIntAndANumberOfTheSameValueShareAnEntryIntentionally(): void
    {
        /** @var Map<int|Number, string> $map */
        $map = new Map();
        $map[new Number(42)] = 'decoded';

        self::assertTrue(isset($map[42]));
        self::assertSame('decoded', $map[42]);

        $map[42] = 'set';

        self::assertCount(1, $map);
        self::assertSame('set', $map[new Number(42)]);
        // The entry keeps its place, and the key it was set with last.
        self::assertSame([[42, 'set']], self::entries($map));

        unset($map[new Number(42)]);

        self::assertFalse(isset($map[42]));
        self::assertSame([], self::entries($map));
    }

    #[DataProvider('provideKeysShareAnEntryOrNotCases')]
    public function testKeysShareAnEntryOrNot(mixed $first, mixed $second, bool $shared): void
    {
        /** @var Map<mixed, string> $map */
        $map = new Map();
        $map[$first] = 'first';

        self::assertSame($shared, isset($map[$second]));

        $map[$second] = 'second';

        self::assertCount($shared ? 1 : 2, $map);
        self::assertSame($shared ? 'second' : 'first', $map[$first]);
        self::assertSame($shared ? [[$second, 'second']] : [[$first, 'first'], [$second, 'second']], self::entries($map));
    }

    /**
     * @return iterable<string, array{mixed, mixed, bool}>
     */
    public static function provideKeysShareAnEntryOrNotCases(): iterable
    {
        yield '1 and "1"' => [1, '1', false];
        yield '1 and a Number of 1' => [1, new Number(1), true];
        yield '1 and true' => [1, true, false];
        yield '"1" and a Number of 1' => ['1', new Number(1), false];
        yield '"1" and true' => ['1', true, false];
        yield 'a Number of 1 and true' => [new Number(1), true, false];

        yield '0 and "0"' => [0, '0', false];
        yield '0 and a Number of 0' => [0, new Number(0), true];
        yield '0 and false' => [0, false, false];
        yield '0 and ""' => [0, '', false];
        yield '"0" and a Number of 0' => ['0', new Number(0), false];
        yield '"0" and false' => ['0', false, false];
        yield '"0" and ""' => ['0', '', false];
        yield 'a Number of 0 and false' => [new Number(0), false, false];
        yield 'a Number of 0 and ""' => [new Number(0), '', false];
        yield 'false and ""' => [false, '', false];
        yield 'true and false' => [true, false, false];

        yield '"true" and true' => ['true', true, false];
        yield '"false" and false' => ['false', false, false];
        yield '"t" and true' => ['t', true, false];
        yield '"f" and false' => ['f', false, false];
        yield '"t" and "f"' => ['t', 'f', false];

        yield '-0 and 0' => [-0, 0, true];
        yield '"-0" and "0"' => ['-0', '0', false];
        yield '"-0" and 0' => ['-0', 0, false];
        yield 'a Number of "-0" and a Number of 0' => [new Number('-0'), new Number(0), true];
        yield 'a Number of "-0" and 0' => [new Number('-0'), 0, true];
        yield '-1 and a Number of -1' => [-1, new Number(-1), true];
        yield 'the smallest int and its Number' => [PHP_INT_MIN, new Number(PHP_INT_MIN), true];
        yield 'the largest int and its Number' => [PHP_INT_MAX, new Number(PHP_INT_MAX), true];

        yield '"01" and 1' => ['01', 1, false];
        yield '"1.0" and 1' => ['1.0', 1, false];
        yield '"01" and "1"' => ['01', '1', false];
        yield 'a Number of "01" and a Number of 1' => [new Number('01'), new Number(1), true];
        yield 'a Number of "01" and 1' => [new Number('01'), 1, true];
        yield 'a Number of "+1" and 1' => [new Number('+1'), 1, true];
        yield 'a Number of "1.0" and a Number of 1' => [new Number('1.0'), new Number(1), true];
        yield 'a Number of "1.0" and 1' => [new Number('1.0'), 1, true];
        yield 'a Number of "10.00" and a Number of 10' => [new Number('10.00'), new Number(10), true];
        yield 'a Number of "10.0" and a Number of 1' => [new Number('10.0'), new Number(1), false];
        yield 'a Number of "0.0" and 0' => [new Number('0.0'), 0, true];
        yield 'a Number of "-1.0" and -1' => [new Number('-1.0'), -1, true];
        yield 'a Number of "1.50" and a Number of "1.5"' => [new Number('1.50'), new Number('1.5'), true];
        yield 'a Number of "1.5" and a Number of 15' => [new Number('1.5'), new Number(15), false];
        yield 'a Number of "1.0" and "1.0"' => [new Number('1.0'), '1.0', false];
        yield 'a Number of "10" and a Number of 1' => [new Number('10'), new Number(1), false];
        yield 'a Number of "100" and a Number of 1' => [new Number('100'), new Number(1), false];
        yield 'a Number of "1.5" and a Number of 1' => [new Number('1.5'), new Number(1), false];
        yield 'the largest uint64 as a Number and as a string' => [
            new Number('18446744073709551615'),
            '18446744073709551615',
            false,
        ];

        yield '"`" and "``"' => ['`', '``', false];
        yield '"`" and "\`"' => ['`', '\`', false];
        yield '"a`" and "a\`"' => ['a`', 'a\`', false];
        yield '"t" and "`t`"' => ['t', '`t`', false];
        yield '"" and "``"' => ['', '``', false];
        yield '"a" and "a\0"' => ['a', "a\0", false];
        yield 'a string and the same string' => ['ключ', 'ключ', true];
        yield 'two Numbers of the same value' => [new Number('18446744073709551615'), new Number('18446744073709551615'), true];
    }

    /**
     * @param iterable<mixed, mixed> $map
     * @return list<array{mixed, mixed}>
     */
    private static function entries(iterable $map): array
    {
        $entries = [];

        foreach ($map as $key => $value) {
            $entries[] = [$key, $value];
        }

        return $entries;
    }
}

enum MapTestKey
{
    case A;
}

final class MapTestStringable implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return '1';
    }
}
