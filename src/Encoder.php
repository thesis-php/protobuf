<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Encoder\EncodingError;

/**
 * @api
 */
interface Encoder
{
    /**
     * @throws EncodingError
     */
    public function encode(object $message): string;
}
