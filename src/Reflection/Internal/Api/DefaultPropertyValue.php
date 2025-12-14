<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

/**
 * @internal
 */
final readonly class DefaultPropertyValue
{
    public function __construct(
        public mixed $value,
    ) {}
}
