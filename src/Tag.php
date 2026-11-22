<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 */
final class Tag
{
    /** The largest field number protobuf allows: 2^29-1. */
    private const int MAX_FIELD_NUMBER = 0x1FFF_FFFF;

    /** @phpstan-ignore property.uninitialized */
    public private(set) Number $number { get => $this->number ??= new Number($this->num << 3 | $this->type->value); }

    /**
     * @throws Exception\InvalidFieldNumber
     * @throws Exception\UnsupportedWireType
     */
    public static function from(Number $number): self
    {
        return self::fromVarint((int) $number->value);
    }

    /**
     * @internal
     * @throws Exception\InvalidFieldNumber
     * @throws Exception\UnsupportedWireType
     */
    public static function fromVarint(int $tag): self
    {
        // A logical shift: a negative tag must not give a negative field number.
        $num = ($tag >> 3) & 0x1FFF_FFFF_FFFF_FFFF;
        $type = $tag & 7;

        if ($num < 1 || $num > self::MAX_FIELD_NUMBER) {
            throw new Exception\InvalidFieldNumber($num);
        }

        return new self(
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
