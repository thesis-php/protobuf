<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Wire;

/**
 * @internal
 */
enum WireType: int
{
    case VARINT = 0;
    case FIXED64 = 1;
    case BYTES = 2;
    case FIXED32 = 5;
}
