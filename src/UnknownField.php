<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 */
final readonly class UnknownField
{
    public function __construct(
        public Tag $tag,
        public Number|string $value,
    ) {}
}
