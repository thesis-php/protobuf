<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class OneOf
{
    /**
     * @param non-empty-list<class-string> $variants
     */
    public function __construct(
        public array $variants,
    ) {}
}
