<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Exception;

use Thesis\Protobuf\Reflection\ReflectionException;

/**
 * A property declared as a protobuf field or oneof that has no value when the object is encoded.
 *
 * @api
 */
final class PropertyNotInitialized extends ReflectionException
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $property
     */
    public function __construct(
        public readonly string $class,
        public readonly string $property,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            \sprintf('The property "%s::$%s" must be initialized before the object is encoded.', $class, $property),
            previous: $previous,
        );
    }
}
