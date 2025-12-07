<?php

declare(strict_types=1);

use Thesis\Protobuf;

$nestedT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\uint32T),
    Protobuf\fieldT(2, Protobuf\stringT),
    Protobuf\fieldT(3, Protobuf\listT(Protobuf\int64T)),
);

$levelT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\stringT),
    Protobuf\fieldT(2, Protobuf\listT($nestedT)),
);

$innerT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\int32T),
    Protobuf\fieldT(2, Protobuf\int32T),
    Protobuf\fieldT(3, Protobuf\listT($levelT)),
);

$deepT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\stringT),
    Protobuf\fieldT(2, $innerT),
);

return Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\int32T),
    Protobuf\fieldT(2, Protobuf\int64T),
    Protobuf\fieldT(3, Protobuf\uint32T),
    Protobuf\fieldT(4, Protobuf\uint64T),
    Protobuf\fieldT(5, Protobuf\sint32T),
    Protobuf\fieldT(6, Protobuf\sint64T),
    Protobuf\fieldT(7, Protobuf\fixed32T),
    Protobuf\fieldT(8, Protobuf\fixed64T),
    Protobuf\fieldT(9, Protobuf\sfixed32T),
    Protobuf\fieldT(10, Protobuf\sfixed64T),
    Protobuf\fieldT(11, Protobuf\floatT),
    Protobuf\fieldT(12, Protobuf\doubleT),
    Protobuf\fieldT(13, Protobuf\boolT),
    Protobuf\fieldT(14, Protobuf\stringT),
    Protobuf\fieldT(15, Protobuf\bytesT),
    Protobuf\fieldT(16, Protobuf\listT(Protobuf\int32T)),
    Protobuf\fieldT(18, Protobuf\listT(Protobuf\stringT)),
    Protobuf\fieldT(19, Protobuf\listT(Protobuf\bytesT)),
    Protobuf\fieldT(20, $nestedT),
    Protobuf\fieldT(21, Protobuf\listT($nestedT)),
    Protobuf\fieldT(22, Protobuf\mapT(Protobuf\stringT, Protobuf\int32T)),
    Protobuf\fieldT(23, Protobuf\mapT(Protobuf\int32T, Protobuf\stringT)),
    Protobuf\fieldT(24, Protobuf\mapT(Protobuf\uint64T, Protobuf\boolT)),
    Protobuf\fieldT(25, Protobuf\mapT(Protobuf\stringT, $nestedT)),
    Protobuf\fieldT(26, Protobuf\mapT(Protobuf\int32T, $nestedT)),
    Protobuf\fieldT(27, $deepT),
);
