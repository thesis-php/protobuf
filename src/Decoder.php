<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Decoder\DecodingError;

/**
 * @api
 */
interface Decoder
{
    /**
     * @template T of object
     * @param class-string<T> $classType
     * @return T
     * @throws DecodingError
     */
    public function decode(string $buffer, string $classType): object;
}
