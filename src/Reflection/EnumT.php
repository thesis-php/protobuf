<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template-covariant T of \BackedEnum
 * @template-implements Type<T, 'repeatable', 'not-indexed', 'map-value'>
 */
final readonly class EnumT implements Type
{
    /**
     * @param class-string<T> $enum
     */
    public function __construct(
        public string $enum,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->enum($this);
    }
}
