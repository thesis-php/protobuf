<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Serde\SerdeVarint;

/**
 * @internal
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

    public function serialize(WriteBuffer $buffer): void
    {
        SerdeVarint::T->serialize($buffer, $this->number);
    }

    /**
     * @param positive-int $num
     */
    public function __construct(
        public readonly int $num,
        public readonly WireType $type,
    ) {}
}
