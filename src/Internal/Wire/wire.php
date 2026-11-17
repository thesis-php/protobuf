<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

use BcMath\Number;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\UnknownFields\UnknownField;
use Thesis\Protobuf\WireType;

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
        WireType::VARINT => toUnsigned($buffer->readVarint()),
        WireType::BYTES => (static function () use ($buffer): string {
            $length = $buffer->readVarint();
            if ($length !== 0) {
                // A negative length is a varint of 2^63 or above: far more than any input holds.
                return $buffer->read($length > 0 ? $length : PHP_INT_MAX);
            }

            return '';
        })(),
    });
}

/**
 * Writes the 64 bits of `value` as a varint: a negative int takes 10 bytes, as an int64 must.
 *
 * @internal
 */
function writeVarint(WriteBuffer $buffer, int $value): void
{
    if ($value >= 0 && $value < 0x80) {
        $buffer->write(\chr($value));

        return;
    }

    $bytes = '';

    while (($value & ~0x7F) !== 0) {
        $bytes .= \chr(($value & 0x7F) | 0x80);
        // A logical shift: the sign bit of a negative int must not be copied into the high bits.
        $value = ($value >> 7) & 0x01FF_FFFF_FFFF_FFFF;
    }

    $buffer->write($bytes . \chr($value & 0x7F));
}

/**
 * The value of 64 bits read as unsigned.
 *
 * @internal
 */
function toUnsigned(int $value): Number
{
    /** @var ?Number $p64 */
    static $p64;

    return $value >= 0 ? new Number($value) : new Number($value) + ($p64 ??= new Number('18446744073709551616'));
}

/**
 * The 64 bits of an unsigned value in [0, 2^64-1]: values of 2^63 and above become negative ints.
 *
 * @internal
 */
function fromUnsigned(Number $value): int
{
    /** @var ?Number $p64 */
    static $p64;

    if ($value->compare(PHP_INT_MAX) > 0) {
        $value -= ($p64 ??= new Number('18446744073709551616'));
    }

    return (int) $value->value;
}

/**
 * @internal
 */
function writeTag(WriteBuffer $buffer, Tag $tag): void
{
    writeVarint($buffer, $tag->num << 3 | $tag->type->value);
}

/**
 * @internal
 * @throws MalformedMessage
 */
function peekTag(ReadBuffer $buffer): Tag
{
    return Tag::fromVarint($buffer->peekVarint());
}

/**
 * @internal
 * @throws MalformedMessage
 */
function readTag(ReadBuffer $buffer): Tag
{
    return Tag::fromVarint($buffer->readVarint());
}
