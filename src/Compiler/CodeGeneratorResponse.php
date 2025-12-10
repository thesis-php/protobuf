<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/compiler/plugin.proto
 */
final readonly class CodeGeneratorResponse
{
    /**
     * @param list<GeneratedFile> $files
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $error = null,
        #[Reflection\Field(2, Reflection\Uint64T::T)]
        public ?int $supportFeatures = null,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public ?int $minimumEdition = null,
        #[Reflection\Field(4, Reflection\Int32T::T)]
        public ?int $maximumEdition = null,
        #[Reflection\Field(15, new Reflection\ListT(
            new Reflection\ObjectT(GeneratedFile::class),
        ))]
        public array $files = [],
    ) {}
}
