<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

/**
 * @internal
 */
final readonly class AttributeCollection
{
    /**
     * @param array<class-string, object> $attributes
     */
    public function __construct(
        public array $attributes = [],
    ) {}

    /**
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class): object
    {
        /** @var T */
        return $this->attributes[$class] ?? throw new \RuntimeException("No attribute '{$class}' found.");
    }
}
