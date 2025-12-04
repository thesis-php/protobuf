<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf;

$messageT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\stringT),
    Protobuf\fieldT(2, Protobuf\listT(Protobuf\int32T)),
    Protobuf\fieldT(3, Protobuf\messageT(
        Protobuf\fieldT(1, Protobuf\mapT(
            keyT: Protobuf\stringT,
            valueT: Protobuf\int32T,
        ))
    ))
);

dd($messageT);
