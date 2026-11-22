<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/maps.php.

use BcMath\Number;
use Thesis\Protobuf\KVPair;
use Thesis\Protobuf\Map;

// An entry typed with a value of Shade, not of the one case given, as Map<string, Shade> needs.
$shade = static fn(string $key, Shade $value): KVPair => new KVPair($key, $value);

// The entries are in the order of their keys, which protoc writes them in.
return [
    'empty' => new Maps(),
    'keys' => new Maps(
        int32Key: [-2147483648 => 'min', -1 => 'minus one', 1 => 'one', 2147483647 => 'max'],
        int64Key: [PHP_INT_MIN => 'min', 1 => 'one', PHP_INT_MAX => 'max'],
        uint32Key: [1 => 'one', 4294967295 => 'max'],
        uint64Key: new Map(
            new KVPair(new Number('1'), 'one'),
            new KVPair(new Number('9223372036854775808'), '2^63'),
            new KVPair(new Number('18446744073709551615'), 'max'),
        ),
        sint32Key: [-2147483648 => 'min', -1 => 'minus one', 2147483647 => 'max'],
        sint64Key: [PHP_INT_MIN => 'min', 1 => 'one', PHP_INT_MAX => 'max'],
        fixed32Key: [2147483648 => '2^31', 4294967295 => 'max'],
        fixed64Key: new Map(
            new KVPair(new Number('2'), 'two'),
            new KVPair(new Number('9223372036854775808'), '2^63'),
            new KVPair(new Number('18446744073709551615'), 'max'),
        ),
        sfixed32Key: [-2147483648 => 'min', 2147483647 => 'max'],
        sfixed64Key: new Map(new KVPair(PHP_INT_MIN, 'min'), new KVPair(PHP_INT_MAX, 'max')),
        boolKey: new Map(new KVPair(false, 'no'), new KVPair(true, 'yes')),
        stringKey: ['A' => 'upper', 'a' => 'lower', 'b' => 'bee', 'key with spaces' => '', 'ключ' => 'значение'],
    ),
    'values' => new Maps(
        bytesValue: new Map(new KVPair('deadbeef', "\xde\xad\xbe\xef"), new KVPair('nul', "\x00")),
        doubleValue: ['inf' => INF, 'minus zero' => -0.0, 'pi' => 3.141592653589793],
        floatValue: ['half' => 0.5, 'minus inf' => -INF],
        enumValue: new Map($shade('dark', Shade::SHADE_DARK), $shade('light', Shade::SHADE_LIGHT)),
        messageValue: ['alice' => new Score(10, 'bob'), 'bob' => new Score(-1)],
    ),
    'zero_entries' => new Maps(
        int32Key: [0 => ''],
        int64Key: [0 => ''],
        uint32Key: [0 => ''],
        uint64Key: new Map(new KVPair(new Number('0'), '')),
        sint32Key: [0 => ''],
        sint64Key: [0 => ''],
        fixed32Key: [0 => ''],
        fixed64Key: new Map(new KVPair(new Number('0'), '')),
        sfixed32Key: [0 => ''],
        sfixed64Key: new Map(new KVPair(0, '')),
        boolKey: new Map(new KVPair(false, '')),
        stringKey: ['' => ''],
        bytesValue: new Map(new KVPair('', '')),
        doubleValue: ['' => 0.0],
        floatValue: ['' => 0.0],
        enumValue: new Map($shade('', Shade::SHADE_NONE)),
        messageValue: ['' => new Score()],
    ),
    // protoc writes the zero value of a key or a value missing from the input.
    'missing_key_and_value' => new Maps(
        int32Key: [0 => 'no key'],
        stringKey: ['no value' => ''],
        messageValue: ['no value' => new Score()],
    ),
];
