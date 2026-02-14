<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class EnumMetadata
{
    /**
     * @param non-empty-string $fqcn
     */
    public function __construct(
        public string $fqcn,
    ) {}
}
