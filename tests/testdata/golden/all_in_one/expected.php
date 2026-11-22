<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/all_in_one.php.

use BcMath\Number;
use Thesis\Protobuf\KVPair;
use Thesis\Protobuf\Map;

// The properties of MegaTest are nullable and null by default, so a field missing from the input is null.
return [
    'empty' => new MegaTest(),
    'zero_values' => new MegaTest(),
    'scalars_ordinary' => new MegaTest(
        aInt32: 150,
        aInt64: -1234567890123,
        aUint32: 300,
        aUint64: new Number('1234567890123456789'),
        aSint32: -150,
        aSint64: 9876543210,
        aFixed32: 3735928559,
        aFixed64: new Number('81985529216486895'),
        aSfixed32: -559038737,
        aSfixed64: -81985529216486895,
        aFloat: 1.5,
        aDouble: -2.25,
        aBool: true,
        aString: 'hello',
        aBytes: 'world',
    ),
    'scalars_plus_one' => new MegaTest(
        aInt32: 1,
        aInt64: 1,
        aUint32: 1,
        aUint64: new Number('1'),
        aSint32: 1,
        aSint64: 1,
        aFixed32: 1,
        aFixed64: new Number('1'),
        aSfixed32: 1,
        aSfixed64: 1,
        aFloat: 1.0,
        aDouble: 1.0,
        aBool: true,
        aString: 'a',
        aBytes: "\x00",
    ),
    'scalars_minus_one' => new MegaTest(
        aInt32: -1,
        aInt64: -1,
        aSint32: -1,
        aSint64: -1,
        aSfixed32: -1,
        aSfixed64: -1,
        aFloat: -1.0,
        aDouble: -1.0,
    ),
    'scalars_max' => new MegaTest(
        aInt32: 2147483647,
        aInt64: PHP_INT_MAX,
        aUint32: 4294967295,
        aUint64: new Number('18446744073709551615'),
        aSint32: 2147483647,
        aSint64: PHP_INT_MAX,
        aFixed32: 4294967295,
        aFixed64: new Number('18446744073709551615'),
        aSfixed32: 2147483647,
        aSfixed64: PHP_INT_MAX,
        aFloat: 3.4028234663852886e38,
        aDouble: PHP_FLOAT_MAX,
    ),
    'scalars_min' => new MegaTest(
        aInt32: -2147483648,
        aInt64: PHP_INT_MIN,
        aSint32: -2147483648,
        aSint64: PHP_INT_MIN,
        aSfixed32: -2147483648,
        aSfixed64: PHP_INT_MIN,
        aFloat: -3.4028234663852886e38,
        aDouble: -PHP_FLOAT_MAX,
    ),
    'varint_boundaries' => new MegaTest(
        aInt32: 127,
        aInt64: 128,
        aUint32: 16383,
        aUint64: new Number('16384'),
        aSint32: -64,
        aSint64: 64,
    ),
    'floats_negative_zero' => new MegaTest(aFloat: -0.0, aDouble: -0.0),
    'floats_infinity' => new MegaTest(aFloat: INF, aDouble: -INF),
    'floats_negative_infinity' => new MegaTest(aFloat: -INF, aDouble: INF),
    'floats_nan' => new MegaTest(aFloat: NAN, aDouble: NAN),
    'floats_smallest' => new MegaTest(aFloat: 1.401298464324817e-45, aDouble: 4.9406564584124654e-324),
    // The float holds 0.1 rounded to 32 bits, which a double holds exactly.
    'floats_rounded' => new MegaTest(aFloat: 0.10000000149011612, aDouble: 0.1),
    'strings' => new MegaTest(
        aString: "Привет, 世界! 🐘 \"quoted\"\n\ttab",
        aBytes: "\x00\x01\x7f\x80\xfe\xff\xde\xad\xbe\xef",
    ),
    'repeated' => new MegaTest(
        rInt32: [0, 1, -1, 2147483647, -2147483648],
        rStrings: ['', 'a', 'ü', '日本'],
        rBytes: ['', "\x00\xff", 'abc'],
    ),
    'nested' => new MegaTest(
        nested: new Nested(7, 'seven', [1, -1, PHP_INT_MAX, PHP_INT_MIN, 0]),
        nestedList: [
            new Nested(),
            new Nested(id: 1),
            new Nested(name: 'two', values: [2]),
        ],
        deep: new DeepNested(
            'deep',
            new DeepNestedInner(-5, 5, [
                new DeepNestedInnerLevel('first', [new Nested(id: 1), new Nested(id: 2, name: 'b')]),
                new DeepNestedInnerLevel(),
                new DeepNestedInnerLevel('third'),
            ]),
        ),
    ),
    'empty_nested' => new MegaTest(nested: new Nested(), deep: new DeepNested(inner: new DeepNestedInner())),
    // In the order of the keys, which protoc writes them in.
    'maps' => new MegaTest(
        mapStrInt32: new Map(
            new KVPair('a', -1),
            new KVPair('b', 2),
            new KVPair('ключ', 2147483647),
        ),
        mapInt32Str: new Map(
            new KVPair(-10, 'minus ten'),
            new KVPair(10, 'ten'),
            new KVPair(2147483647, 'max'),
        ),
        mapUint64Bool: new Map(
            new KVPair(new Number('1'), true),
            new KVPair(new Number('9223372036854775808'), false),
            new KVPair(new Number('18446744073709551615'), true),
        ),
        mapStrMsg: new Map(
            new KVPair('e', new Nested()),
            new KVPair('x', new Nested(1, 'one', [1, 2])),
        ),
        mapInt32Msg: new Map(
            new KVPair(-1, new Nested(name: 'neg')),
            new KVPair(1, new Nested(id: 4294967295)),
        ),
    ),
    'map_zero_entries' => new MegaTest(
        mapStrInt32: new Map(new KVPair('', 0)),
        mapInt32Str: new Map(new KVPair(0, '')),
        mapUint64Bool: new Map(new KVPair(new Number('0'), false)),
        mapStrMsg: new Map(new KVPair('', new Nested())),
        mapInt32Msg: new Map(new KVPair(0, new Nested())),
    ),
];
