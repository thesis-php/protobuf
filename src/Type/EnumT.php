<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Internal\IntBackedEnum;
use Thesis\Protobuf\Type;

/**
 * @template-implements Type<\BackedEnum, 'repeatable', 'not-indexed'>
 * @template-implements Listable<\BackedEnum>
 */
final readonly class EnumT implements
    Type,
    Listable
{
    /** @use Listed<\BackedEnum> */
    use Listed;

    /**
     * @param class-string<\BackedEnum> $enum
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
