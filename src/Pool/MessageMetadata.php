<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class MessageMetadata
{
    /**
     * @param non-empty-string $fqcn
     */
    public function __construct(
        public string $fqcn,
    ) {}
}
