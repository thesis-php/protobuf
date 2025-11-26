<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 */
final class Tag
{
    public private(set) Number $number {
        get => $this->number ??= new Number($this->num << 3 | $this->type->value);
    }

    public static function from(Number $number): self
    {
        $tag = (int) $number->value;

        /** @var positive-int $num */
        $num = $tag >> 3;
        $type = $tag & 7;

        return new self(
            $num,
            WireType::from($type),
        );
    }

    /**
     * @param positive-int $num
     */
    public function __construct(
        public readonly int $num,
        public readonly WireType $type,
    ) {}
}
