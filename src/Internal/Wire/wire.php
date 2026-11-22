<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

use BcMath\Number;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\MalformedGroup;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
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
 *   bytes   - 1 ≤ size ≤ 10 + length,
 *   group   - the fields up to its end group tag, which is read but not returned.
 *
 * @internal
 * @throws MalformedMessage
 * @throws RecursionLimitExceeded
 */
function discardUnknown(ReadBuffer $buffer, Tag $tag): UnknownField
{
    return new UnknownField($tag, match ($tag->type) {
        WireType::FIXED32 => $buffer->read(4),
        WireType::FIXED64 => $buffer->read(8),
        WireType::VARINT => toUnsigned($buffer->readVarint()),
        WireType::BYTES => readBytes($buffer),
        WireType::SGROUP => readGroup($buffer, $tag->num),
        WireType::EGROUP => throw new MalformedGroup(
            \sprintf('Unexpected end group tag of field %d: no group is open.', $tag->num),
        ),
    });
}

/**
 * Reads a length-delimited value.
 *
 * @internal
 * @throws MalformedMessage
 */
function readBytes(ReadBuffer $buffer): string
{
    $length = $buffer->readVarint();
    if ($length !== 0) {
        // A negative length is a varint of 2^63 or above: far more than any input holds.
        return $buffer->read($length > 0 ? $length : PHP_INT_MAX);
    }

    return '';
}

/**
 * Reads the fields of a group, whose start group tag is read already, up to the end group tag of the same field.
 * Nested groups are tracked on a list rather than by recursion; each of them, this one too, is a level of nesting
 * counted against the recursion limit of the buffer, as a nested message is.
 *
 * @internal
 * @param positive-int $num the field number of the group
 * @return string the fields of the group as they were written, without its end group tag
 * @throws MalformedMessage
 * @throws RecursionLimitExceeded
 */
function readGroup(ReadBuffer $buffer, int $num): string
{
    $start = $buffer->position();
    $open = [$num];
    $buffer->enterNested();

    while (true) {
        if (\count($buffer) === 0) {
            throw new BufferUnderflow("Unexpected end of input: the group of field {$num} is not closed.");
        }

        $end = $buffer->position();
        $tag = readTag($buffer);

        if ($tag->type === WireType::SGROUP) {
            $buffer->enterNested();
            $open[] = $tag->num;
        } elseif ($tag->type !== WireType::EGROUP) {
            match ($tag->type) {
                WireType::VARINT => $buffer->readVarint(),
                WireType::FIXED32 => $buffer->read(4),
                WireType::FIXED64 => $buffer->read(8),
                WireType::BYTES => readBytes($buffer),
            };
        } elseif (($expected = array_pop($open)) !== $tag->num) {
            throw new MalformedGroup(
                \sprintf('Unexpected end group tag of field %d: the group of field %d is open.', $tag->num, $expected),
            );
        } else {
            $buffer->leaveNested();

            if ($open === []) {
                return substr($buffer->bytesSince($start), 0, $end - $start);
            }
        }
    }
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
 * The encoded tag of a field: computed once per field number and wire type.
 *
 * @internal
 * @param positive-int $num
 * @return non-empty-string
 */
function tagBytes(int $num, WireType $type): string
{
    /** @var array<int, non-empty-string> $tags */
    static $tags = [];

    $key = $num << 3 | $type->value;

    if (!isset($tags[$key])) {
        $buffer = new ByteBuffer();
        writeVarint($buffer, $key);
        /** @var non-empty-string $tag */
        $tag = $buffer->flush();
        $tags[$key] = $tag;
    }

    return $tags[$key];
}

/**
 * @internal
 * @throws MalformedMessage
 */
function readTag(ReadBuffer $buffer): Tag
{
    return Tag::fromVarint($buffer->readTag());
}
