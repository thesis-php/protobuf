<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
interface Decoder
{
    /**
     * @template T of object
     * @param class-string<T> $classType
     * @return T
     * @throws ProtobufException
     */
    public function decode(string $buffer, string $classType): object;
}
