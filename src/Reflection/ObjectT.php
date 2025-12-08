<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template-covariant T of object
 * @template-implements Type<T, 'repeatable', 'not-indexed', 'map-value'>
 */
final readonly class ObjectT implements Type
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        public string $class,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->object($this);
    }
}
