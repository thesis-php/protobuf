<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

use BcMath\Number;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Serde\SerdeVarint;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\UnknownField;
use Thesis\Protobuf\WireType;
use Thesis\Varint;

/**
 * Return bytes from the buffer corresponding to the size of each type:
 *   fixed32 - 4,
 *   fixed64 - 8,
 *   varint  - 1 ≤ size ≤ 10,
 *   bytes   - 1 ≤ size ≤ 10 + length.
 *
 * @internal
 * @throws BufferUnderflow
 */
function discardUnknown(ReadBuffer $buffer, Tag $tag): UnknownField
{
    return new UnknownField($tag, match ($tag->type) {
        WireType::FIXED32 => $buffer->read(4),
        WireType::FIXED64 => $buffer->read(8),
        WireType::VARINT => readVarint($buffer),
        WireType::BYTES => (static function () use ($buffer): string {
            $length = (int) readVarint($buffer)->value;
            if ($length > 0) {
                return $buffer->read($length);
            }

            return '';
        })(),
    });
}

/**
 * @internal
 * @throws BufferUnderflow
 */
function readVarint(ReadBuffer $buffer): Number
{
    $number = Varint\BcMath::Codec->decodeVarintSized($buffer->peek(10));
    $buffer->read($number->size);

    return $number->value;
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
