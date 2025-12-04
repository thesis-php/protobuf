<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<\BackedEnum>
 */
final readonly class EnumT implements Type
{
    /**
     * @param class-string<\BackedEnum> $enum
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
