<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class NullValueKind implements ValueKind
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(NullValue::class))]
        public NullValue $value,
    ) {}
}
