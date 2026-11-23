<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
enum WireType: int
{
    case VARINT = 0;
    case FIXED64 = 1;
    case BYTES = 2;
    /** The start of a group: a nested message written up to its end group tag instead of after its length. */
    case SGROUP = 3;
    /** The end of a group. */
    case EGROUP = 4;
    case FIXED32 = 5;
}
