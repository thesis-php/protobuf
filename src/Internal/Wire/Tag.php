<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

use BcMath\Number;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Serde\SerdeVarint;
use Thesis\Varint;

/**
 * @internal
 */
final class Tag
{
    /** @phpstan-ignore property.uninitialized */
    public private(set) Number $number { get => $this->number ??= new Number($this->num << 3 | $this->type->value); }

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

/**
 * @internal
 */
function writeTag(WriteBuffer $buffer, Tag $tag): void
{
    SerdeVarint::T->serialize($buffer, $tag->number);
}

/**
 * @internal
 * @throws BufferUnderflow
 */
function peekTag(ReadBuffer $buffer): Tag
{
    $number = Varint\BcMath::Codec->decodeVarintSized($buffer->peek(10));

    return Tag::from($number->value);
}

/**
 * @internal
 * @throws BufferUnderflow
 */
function readTag(ReadBuffer $buffer): Tag
{
    return Tag::from(readVarint($buffer));
}
