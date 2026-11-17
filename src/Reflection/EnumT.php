<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Internal\IntBackedEnum;

/**
 * @api
 * @template-covariant T of \BackedEnum
 * @template-implements Type<T, 'repeatable', 'not-indexed', 'map-value'>
 */
final readonly class EnumT implements Type
{
    /**
     * @param class-string<T> $enum
     * @throws InvalidSchema if the class is not an int-backed enum
     */
    public function __construct(
        public string $enum,
    ) {
        IntBackedEnum::check($enum);
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->enum($this);
    }
}
