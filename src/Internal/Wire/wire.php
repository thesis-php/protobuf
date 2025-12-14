<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

use BcMath\Number;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Varint;

/**
 * Removes bytes from the buffer corresponding to the size of each type:
 *   fixed32 - 4,
 *   fixed64 - 8,
 *   varint  - 1 ≤ size ≤ 10,
 *   bytes   - 1 ≤ size ≤ 10 + length.
 *
 * @internal
 * @throws BufferUnderflow
 */
function discardUnknown(ReadBuffer $buffer, Tag $tag): void
{
    switch ($tag->type) {
        case WireType::fixed32:
            $buffer->read(4);
            break;
        case WireType::fixed64:
            $buffer->read(8);
            break;
        case WireType::varint:
            readVarint($buffer);
            break;
        case WireType::bytes:
            $length = (int) readVarint($buffer)->value;
            if ($length > 0) {
                $buffer->read($length);
            }
            break;
    }
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
