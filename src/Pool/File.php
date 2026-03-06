<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class File
{
    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $dependencies
     * @param list<File\MessageDescriptor> $messages
     * @param list<File\EnumDescriptor> $enums
     * @param list<File\ServiceDescriptor> $services
     */
    public function __construct(
        public string $name,
        public array $dependencies = [],
        public array $messages = [],
        public array $enums = [],
        public array $services = [],
    ) {}
}
