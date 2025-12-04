<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 * @template T
 */
final readonly class FieldDescriptor
{
    /**
     * @param positive-int $num
     * @param Value<T> $value
     */
    public function __construct(
        public int $num,
        public Value $value,
    ) {}
}
