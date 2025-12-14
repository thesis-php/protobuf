<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Exception;

use Thesis\Protobuf\Reflection\ReflectionException;

/**
 * @api
 */
final class PropertyUninitialized extends ReflectionException
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $propertyName
     */
    public function __construct(string $class, string $propertyName, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Property `{$class}::\${$propertyName}` cannot be initialized: make it nullable or provide a default value.",
            $code,
            $previous,
        );
    }
}
