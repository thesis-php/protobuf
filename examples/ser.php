<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Protobuf;

$message = Protobuf\message(
    Protobuf\fieldOf(1, Protobuf\stringOf('kafkiansky')),
    Protobuf\fieldOf(2, Protobuf\messageOf(
        Protobuf\fieldOf(1, Protobuf\stringOf('thesis')),
        Protobuf\fieldOf(2, Protobuf\stringOf('maintainer')),
    ))
);

$serializer = new Protobuf\Serializer();

$bin = $serializer->serialize($message);

/** @phpstan-ignore equal.notAllowed */
\assert($serializer->deserialize($message->type(), $bin) == $message);
