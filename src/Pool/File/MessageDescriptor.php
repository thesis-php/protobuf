<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool\File;

/**
 * @api
 */
final readonly class MessageDescriptor
{
    /**
     * @param non-empty-string $name
     * @param class-string $fqcn
     */
    public function __construct(
        public string $name,
        public string $fqcn,
    ) {}
}
