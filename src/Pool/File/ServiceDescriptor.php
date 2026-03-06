<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool\File;

/**
 * @api
 */
final readonly class ServiceDescriptor
{
    /**
     * @param non-empty-string $name
     * @param ?class-string $clientFqcn
     * @param ?class-string $serverFqcn
     */
    public function __construct(
        public string $name,
        public ?string $clientFqcn = null,
        public ?string $serverFqcn = null,
    ) {}
}
