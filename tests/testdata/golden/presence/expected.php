<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/presence.php.

use BcMath\Number;

return [
    'empty' => new Presence(),
    'implicit_zero_values' => new Presence(),
    'implicit_values' => new Presence(
        iInt32: -1,
        iUint64: new Number('18446744073709551615'),
        iFloat: 0.5,
        iDouble: -1e-300,
        iBool: true,
        iString: 'ü',
        iBytes: "\x00",
        iSign: Sign::SIGN_NEGATIVE,
    ),
    'implicit_negative_zero' => new Presence(iFloat: -0.0, iDouble: -0.0),
    'optional_zero_values' => new Presence(
        oInt32: 0,
        oInt64: 0,
        oUint32: 0,
        oUint64: new Number('0'),
        oSint32: 0,
        oSint64: 0,
        oFixed32: 0,
        oFixed64: new Number('0'),
        oSfixed32: 0,
        oSfixed64: 0,
        oFloat: 0.0,
        oDouble: 0.0,
        oBool: false,
        oString: '',
        oBytes: '',
        oSign: Sign::SIGN_ZERO,
        point: new Point(),
    ),
    'optional_values' => new Presence(
        oInt32: -2147483648,
        oInt64: PHP_INT_MAX,
        oUint32: 4294967295,
        oUint64: new Number('18446744073709551615'),
        oSint32: -1,
        oSint64: PHP_INT_MIN,
        oFixed32: 1,
        oFixed64: new Number('18446744073709551615'),
        oSfixed32: 2147483647,
        oSfixed64: PHP_INT_MIN,
        oFloat: -INF,
        oDouble: 0.1,
        oBool: true,
        oString: 'строка',
        oBytes: "\xff",
        oSign: Sign::SIGN_POSITIVE,
        point: new Point(-1, 1),
    ),
    'enum_bounds' => new Presence(iSign: Sign::SIGN_MIN, oSign: Sign::SIGN_MAX),
    'enum_negative' => new Presence(iSign: Sign::SIGN_MAX, oSign: Sign::SIGN_NEGATIVE),
];
