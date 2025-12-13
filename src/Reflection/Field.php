<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Field
{
    /**
     * @template T
     * @param positive-int $num
     * @param Type<T, *, *, *> $type
     * @param ?T $default
     */
    public function __construct(
        public int $num,
        public Type $type,
        public mixed $default = null,
    ) {}
}
