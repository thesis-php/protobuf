<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Exception;

use Thesis\Protobuf\Reflection\ReflectionException;

/**
 * A property declared as a protobuf field or oneof that cannot be one, e.g. a static property or a property whose
 * PHP type cannot hold the values of its protobuf type. It is a configuration error, reported when the class is
 * reflected for the first time.
 *
 * @api
 */
final class InvalidProperty extends ReflectionException
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $property
     * @param non-empty-string $reason
     */
    public function __construct(
        public readonly string $class,
        public readonly string $property,
        string $reason,
    ) {
        parent::__construct(\sprintf('The property "%s::$%s" %s.', $class, $property, $reason));
    }
}
