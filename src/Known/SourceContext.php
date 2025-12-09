<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/source_context.proto
 */
final readonly class SourceContext
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $filename,
    ) {}
}
