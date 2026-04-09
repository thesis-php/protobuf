<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFields;

use BcMath\Number;
use Thesis\Protobuf\Tag;

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
