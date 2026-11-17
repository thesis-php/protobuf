<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Internal\Wire;

/**
 * Writes the contents of `src` to `dst` as a length-delimited record.
 *
 * @internal
 */
function copy(ByteBuffer $src, WriteBuffer $dst): void
{
    $written = $src->flush();
    Wire\writeVarint($dst, \strlen($written));
    if ($written !== '') {
        $dst->write($written);
    }
}

/**
 * Reads the length of a length-delimited record and limits the buffer to its body.
 *
 * @internal
 * @return int the previous limit, to restore with popLimit() once the body is read
 * @throws MalformedMessage
 */
function enterRecord(ReadBuffer $buffer): int
{
    $length = $buffer->readVarint();

    // A negative length is a varint of 2^63 or above: far more than any input holds.
    return $buffer->pushLimit($length >= 0 ? $length : PHP_INT_MAX);
}
