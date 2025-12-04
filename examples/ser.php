<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf;

enum Status: int
{
    case UNKNOWN = 0;
    case OK = 1;
    case FAIL = 2;
}

$message = Protobuf\message(
    Protobuf\fieldOf(1, Protobuf\uint32Of(123)),
    Protobuf\fieldOf(2, Protobuf\stringOf('Example')),
    Protobuf\fieldOf(3, Protobuf\boolOf(true)),
    Protobuf\fieldOf(4, Protobuf\int32Of(42)),
    Protobuf\fieldOf(5, Protobuf\floatOf(98.5)),
    Protobuf\fieldOf(6, Protobuf\doubleOf(12345.67)),
    Protobuf\fieldOf(7, Protobuf\int32T->list([1, 2, 3, 4, 5])),
    Protobuf\fieldOf(8, Protobuf\stringT->list(['alpha', 'beta', 'gamma'])),
    Protobuf\fieldOf(9, Protobuf\enumOf(Status::OK)),
    Protobuf\fieldOf(10, Protobuf\listOf(
        Protobuf\messageT(
            Protobuf\fieldT(1, Protobuf\uint32T),
            Protobuf\fieldT(2, Protobuf\stringT),
            Protobuf\fieldT(3, Protobuf\floatT),
            Protobuf\fieldT(4, Protobuf\listT(Protobuf\int64T)),
        ),
        [
            Protobuf\message(
                Protobuf\fieldOf(1, Protobuf\uint32Of(1)),
                Protobuf\fieldOf(2, Protobuf\stringOf('Item 1')),
                Protobuf\fieldOf(3, Protobuf\floatOf(1.5)),
                Protobuf\fieldOf(4, Protobuf\int64T->list([10, 20, 30])),
            ),
            Protobuf\message(
                Protobuf\fieldOf(1, Protobuf\uint32Of(2)),
                Protobuf\fieldOf(2, Protobuf\stringOf('Item 2')),
                Protobuf\fieldOf(3, Protobuf\floatOf(2.75)),
                Protobuf\fieldOf(4, Protobuf\int64T->list([100, 200, 300])),
            ),
        ],
    )),
    Protobuf\fieldOf(11, Protobuf\fixed32Of(0xABCD1234)),
    Protobuf\fieldOf(12, Protobuf\sfixed64Of(-9000000000)),
    Protobuf\fieldOf(13, Protobuf\sint32Of(-42)),
    Protobuf\fieldOf(14, Protobuf\uint64Of(9999999999999)),
    Protobuf\fieldOf(15, Protobuf\messageOf(
        Protobuf\fieldOf(1, Protobuf\stringOf('tester')),
        Protobuf\fieldOf(2, Protobuf\stringOf('2025-12-04T12:00:00Z')),
        Protobuf\fieldOf(3, Protobuf\boolOf(true)),
        Protobuf\fieldOf(4, Protobuf\stringT->list(['ok', 'verified', 'for serialization test'])),
        Protobuf\fieldOf(5, Protobuf\messageOf(
            Protobuf\fieldOf(1, Protobuf\stringOf('kek')),
        )),
    )),
    Protobuf\fieldOf(16, Protobuf\mapOf(
        Protobuf\stringT,
        Protobuf\int32T,
        [
            'x' => 1,
        ],
    ))
);

$serializer = new Protobuf\Serializer();

var_dump(bin2hex($serializer->serialize($message)) === '087b12074578616d706c651801202a2d0000c54231295c8fc2d51cc8403a0501020304054205616c706861420462657461420567616d6d6148015214080112064974656d20311d0000c03f22030a141e5216080212064974656d20321d00003040220564c801ac025d3412cdab6100e68ee7fdffffff685370ffbfcaf384a3027a4d0a067465737465721214323032352d31322d30345431323a30303a30305a180122026f6b220876657269666965642216666f722073657269616c697a6174696f6e20746573742a050a036b656b8201050a01781001');
