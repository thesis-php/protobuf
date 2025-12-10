<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/compiler/plugin.proto
 */
final readonly class CodeGeneratorRequest
{
    /**
     * @param list<string> $filesToGenerate
     * @param list<FileDescriptorProto> $protoFiles
     * @param list<FileDescriptorProto> $sourceFileDescriptors
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\StringT::T))]
        public array $filesToGenerate = [],
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $parameter = null,
        #[Reflection\Field(15, new Reflection\ListT(
            new Reflection\ObjectT(FileDescriptorProto::class),
        ))]
        public array $protoFiles = [],
        #[Reflection\Field(17, new Reflection\ListT(
            new Reflection\ObjectT(FileDescriptorProto::class),
        ))]
        public array $sourceFileDescriptors = [],
        #[Reflection\Field(3, new Reflection\ObjectT(CompilerVersion::class))]
        public ?CompilerVersion $compilerVersion = null,
    ) {}
}
