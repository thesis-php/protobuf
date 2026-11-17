<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use BcMath\Number;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Internal\Serde\SerdeVarint;
use Thesis\Protobuf\Internal\Wire;

/**
 * Writes the contents of `src` to `dst` as a length-delimited record.
 *
 * @internal
 */
function copy(ByteBuffer $src, WriteBuffer $dst): void
{
    $written = $src->flush();
    SerdeVarint::T->serialize($dst, new Number(\strlen($written)));
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
    return $buffer->pushLimit((int) Wire\readVarint($buffer)->value);
}
