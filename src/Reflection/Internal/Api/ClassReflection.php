<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

/**
 * @internal
 * @template T of object
 */
final readonly class ClassReflection
{
    /**
     * @param \ReflectionClass<T> $class
     * @param list<PropertyReflection> $properties
     */
    public function __construct(
        public \ReflectionClass $class,
        public array $properties,
    ) {}
}
