<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class ServiceMetadata
{
    /**
     * @param ?non-empty-string $clientFqcn
     * @param ?non-empty-string $serverFqcn
     * @param ?non-empty-string $serverRegistryFqcn
     */
    public function __construct(
        public ?string $clientFqcn = null,
        public ?string $serverFqcn = null,
        public ?string $serverRegistryFqcn = null,
    ) {}
}
