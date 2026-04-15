<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
interface Encoder
{
    /**
     * @throws ProtobufException
     */
    public function encode(object $message): string;
}
