<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template T
 */
final readonly class Field
{
    /**
     * @param positive-int $num
     * @param Type<T> $type
     */
    public function __construct(
        public int $num,
        public Type $type,
    ) {}
}
