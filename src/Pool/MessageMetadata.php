<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 * @template-covariant T of object
 */
final readonly class MessageMetadata
{
    /**
     * @param class-string<T> $fqcn
     */
    public function __construct(
        public string $fqcn,
    ) {}
}
