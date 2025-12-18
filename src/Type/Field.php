<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * @template T
 */
final readonly class Field
{
    /**
     * @param positive-int $num
     * @param Type<T, *, *, *> $type
     */
    public function __construct(
        public int $num,
        public Type $type,
    ) {}
}
