<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

use BcMath\Number;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Exception\MalformedVarint;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Serde\SerdeVarint;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\UnknownFields\UnknownField;
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
 * @throws MalformedMessage
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
 * @throws MalformedMessage
 */
function readVarint(ReadBuffer $buffer): Number
{
    $number = decodeVarint($buffer->peek(10));
    $buffer->read($number->size);

    return $number->value;
}

/**
 * @internal
 * @param non-empty-string $bytes up to 10 bytes from the start of a varint
 * @throws MalformedMessage
 */
function decodeVarint(string $bytes): Varint\Sized
{
    try {
        return Varint\BcMath::Codec->decodeVarintSized($bytes);
    } catch (Varint\MalformedVarintNumber) {
        // No terminating byte among the bytes given: either the input ends or the varint is too long.
        throw \strlen($bytes) < 10
            ? new BufferUnderflow('Unexpected end of input: truncated varint.')
            : new MalformedVarint('Malformed varint: longer than 10 bytes.');
    }
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
 * @throws MalformedMessage
 */
function peekTag(ReadBuffer $buffer): Tag
{
    return Tag::from(decodeVarint($buffer->peek(10))->value);
}

/**
 * @internal
 * @throws MalformedMessage
 */
function readTag(ReadBuffer $buffer): Tag
{
    return Tag::from(readVarint($buffer));
}
