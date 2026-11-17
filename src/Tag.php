<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 */
final class Tag
{
    /** @phpstan-ignore property.uninitialized */
    public private(set) Number $number { get => $this->number ??= new Number($this->num << 3 | $this->type->value); }

    /**
     * @throws Exception\UnsupportedWireType
     */
    public static function from(Number $number): self
    {
        return self::fromVarint((int) $number->value);
    }

    /**
     * @internal
     * @throws Exception\UnsupportedWireType
     */
    public static function fromVarint(int $tag): self
    {
        // A logical shift: a negative tag must not give a negative field number.
        $num = ($tag >> 3) & 0x1FFF_FFFF_FFFF_FFFF;
        $type = $tag & 7;

        return new self(
            // Field number 0 is invalid but still accepted here, as it always was.
            /** @phpstan-ignore argument.type */
            $num,
            WireType::tryFrom($type) ?? throw new Exception\UnsupportedWireType($type, $num),
        );
    }

    public function equal(self $tag): bool
    {
        return $tag->num === $this->num && $tag->type === $this->type;
    }

    /**
     * @param positive-int $num
     */
    public function __construct(
        public readonly int $num,
        public readonly WireType $type,
    ) {}
}
