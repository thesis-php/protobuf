<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FileDescriptorSet
{
    /**
     * @param list<FileDescriptorProto> $files
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(
            new Reflection\ObjectT(FileDescriptorProto::class),
        ))]
        public array $files = [],
    ) {}
}
