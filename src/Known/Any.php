<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/any.proto
 */
final readonly class Any
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $typeUrl,
        #[Reflection\Field(2, Reflection\BytesT::T)]
        public string $value,
    ) {}
}
