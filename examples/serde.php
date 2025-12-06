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

/**
 * message Item {
 *   uint32 index = 1;
 *   string label = 2;
 *   float weight = 3;
 *   repeated int64 values = 4;
 * }
 */
$itemT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\uint32T),
    Protobuf\fieldT(2, Protobuf\stringT),
    Protobuf\fieldT(3, Protobuf\floatT),
    Protobuf\fieldT(4, Protobuf\listT(Protobuf\int64T)),
);

/**
 * message Footer {
 *   string title = 1;
 * }
 */
$footerT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\stringT),
);

/**
 * message Meta {
 *   string created_by = 1;
 *   string created_at = 2;
 *   bool verified = 3;
 *   repeated string comments = 4;
 *   Footer footer = 5;
 * }
 */
$metaT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\stringT),
    Protobuf\fieldT(2, Protobuf\stringT),
    Protobuf\fieldT(3, Protobuf\boolT),
    Protobuf\fieldT(4, Protobuf\listT(Protobuf\stringT)),
    Protobuf\fieldT(5, $footerT),
);

/**
 * map<fixed32, Meta>
 */
$metasT = Protobuf\mapT(
    Protobuf\fixed32T,
    $metaT,
);

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
            Protobuf\fieldOf(1, Protobuf\stringOf('x')),
        )),
    )),
    Protobuf\fieldOf(16, Protobuf\mapOf(
        Protobuf\stringT,
        Protobuf\int32T,
        [
            'x' => 1,
        ],
    )),
    Protobuf\fieldOf(17, $metasT->map([
        1 => Protobuf\message(
            Protobuf\fieldOf(1, Protobuf\stringOf('kafkiansky')),
            Protobuf\fieldOf(2, Protobuf\stringOf('2025-12-05T12:00:00Z')),
            Protobuf\fieldOf(3, Protobuf\boolOf(true)),
            Protobuf\fieldOf(4, Protobuf\stringT->list(['thesis', 'protobuf'])),
            Protobuf\fieldOf(5, Protobuf\messageOf(
                Protobuf\fieldOf(1, Protobuf\stringOf('y')),
            )),
        ),
    ])),
);

$serializer = new Protobuf\Serializer();

$buffer = $serializer->serialize($message);
\assert($buffer !== '');

$serde = $serializer->deserialize(
    $message->type(),
    $buffer,
);

$hex = bin2hex($serializer->serialize($serde));
$reference = '087b12074578616d706c651801202a2d0000c54231295c8fc2d51cc8403a0501020304054205616c706861420462657461420567616d6d6148015214080112064974656d20311d0000c03f22030a141e5216080212064974656d20321d00003040220564c801ac025d3412cdab6100e68ee7fdffffff685370ffbfcaf384a3027a4b0a067465737465721214323032352d31322d30345431323a30303a30305a180122026f6b220876657269666965642216666f722073657269616c697a6174696f6e20746573742a030a01788201050a017810018a01420d01000000123b0a0a6b61666b69616e736b791214323032352d31322d30355431323a30303a30305a18012206746865736973220870726f746f6275662a030a0179';

var_dump($hex === $reference);
