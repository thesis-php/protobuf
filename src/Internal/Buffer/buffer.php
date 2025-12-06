<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use BcMath\Number;
use Thesis\Protobuf\BufferUnderflow;
use Thesis\Protobuf\Internal\Serde\SerdeVarint;
use Thesis\Protobuf\Internal\Wire;

/**
 * @internal
 */
function copy(ReadBuffer $src, WriteBuffer $dst): void
{
    $written = $src->flush();
    if ($written !== '') {
        SerdeVarint::T->serialize($dst, new Number(\strlen($written)));
        $dst->write($written);
    }
}

/**
 * @internal
 * @throws BufferUnderflow
 */
function slice(ReadBuffer $src): ReadBuffer
{
    $dst = new ByteBuffer();
    $length = (int) Wire\readVarint($src)->value;

    if ($length > 0) {
        $dst->write($src->read($length));
    }

    return $dst;
}
