<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

/**
 * @internal
 */
final readonly class PropertyReflection
{
    public function __construct(
        public \ReflectionProperty $reflection,
        public AttributeCollection $attributes,
        public ?DefaultPropertyValue $default = null,
    ) {}
}
