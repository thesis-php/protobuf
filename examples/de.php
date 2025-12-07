<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Protobuf;

$organizationT = Protobuf\messageT(
    // name
    Protobuf\fieldT(1, Protobuf\stringT),
    // role
    Protobuf\fieldT(2, Protobuf\stringT),
);

$userT = Protobuf\messageT(
    // username
    Protobuf\fieldT(1, Protobuf\stringT),
    // organization
    Protobuf\fieldT(2, $organizationT),
);

$serializer = new Protobuf\Serializer();

$bin = hex2bin('0a0a6b61666b69616e736b7912140a06746865736973120a6d61696e7461696e6572');
\assert(\is_string($bin));

$user = $serializer->deserialize($userT, $bin);

dump($user);
