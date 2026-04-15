<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Exception;

use Thesis\Protobuf\Reflection\ReflectionException;

/**
 * @api
 */
final class PropertyRequired extends ReflectionException
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $property
     */
    public function __construct(
        public readonly string $class,
        public readonly string $property,
    ) {
        parent::__construct(\sprintf('The property "%s::$%s" is required and cannot be initialized implicitly.', $class, $property));
    }
}
