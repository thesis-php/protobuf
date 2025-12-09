<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/field_mask.proto
 */
final readonly class FieldMask
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\StringT::T))]
        public array $paths,
    ) {}
}
