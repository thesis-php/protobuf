<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/repeated.php.

use BcMath\Number;

$int32 = [0, 1, -1, 2147483647, -2147483648];
$int64 = [0, 1, -1, PHP_INT_MAX, PHP_INT_MIN];
$uint32 = [0, 1, 4294967295];
$uint64 = [new Number('0'), new Number('1'), new Number('18446744073709551615')];
$float = [0.0, -0.0, 1.5, INF, -INF];
$double = [0.0, -0.0, -2.25, INF, -INF];
$bool = [false, true, true];
$direction = [Direction::DIRECTION_NONE, Direction::DIRECTION_UP, Direction::DIRECTION_DOWN];

$packed = new Repeated(
    pInt32: $int32,
    pInt64: $int64,
    pUint32: $uint32,
    pUint64: $uint64,
    pSint32: $int32,
    pSint64: $int64,
    pFixed32: $uint32,
    pFixed64: $uint64,
    pSfixed32: $int32,
    pSfixed64: $int64,
    pFloat: $float,
    pDouble: $double,
    pBool: $bool,
    pDirection: $direction,
);

$unpacked = new Repeated(
    uInt32: $int32,
    uInt64: $int64,
    uUint32: $uint32,
    uUint64: $uint64,
    uSint32: $int32,
    uSint64: $int64,
    uFixed32: $uint32,
    uFixed64: $uint64,
    uSfixed32: $int32,
    uSfixed64: $int64,
    uFloat: $float,
    uDouble: $double,
    uBool: $bool,
    uDirection: $direction,
);

return [
    'empty' => new Repeated(),
    'packed' => $packed,
    'unpacked' => $unpacked,
    'single_zero_elements' => new Repeated(
        pInt32: [0],
        pBool: [false],
        pDirection: [Direction::DIRECTION_NONE],
        uInt32: [0],
        uBool: [false],
        uDirection: [Direction::DIRECTION_NONE],
        strings: [''],
        blobs: [''],
        items: [new RepeatedItem()],
    ),
    'not_packable' => new Repeated(
        strings: ['a', '', 'ü', '日本語'],
        blobs: ["\x00", "\xff\xfe", ''],
        items: [new RepeatedItem('first'), new RepeatedItem(), new RepeatedItem('third')],
    ),
    'many_elements' => new Repeated(pInt32: range(1, 200), uInt32: range(1, 5)),
    'decode-only/packed' => $packed,
    'decode-only/unpacked' => $unpacked,
];
