<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

/**
 * @internal
 */
final readonly class PropertyReflection
{
    /**
     * @param bool $mapAsArray whether the property is a map field that holds the map as an array, since its type
     *        accepts an array, but not a Map
     */
    public function __construct(
        public \ReflectionProperty $reflection,
        public AttributeCollection $attributes,
        public ?DefaultPropertyValue $default = null,
        public bool $mapAsArray = false,
    ) {}
}
