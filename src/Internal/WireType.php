<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal;

/**
 * @internal
 */
enum WireType: int
{
    case varint = 0;
    case fixed64 = 1;
    case bytes = 2;
    case fixed32 = 5;
}
