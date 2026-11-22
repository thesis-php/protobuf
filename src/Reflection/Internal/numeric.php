<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal;

use BcMath\Number;

/**
 * @internal
 */
function zeroed(mixed $value): bool
{
    if (\is_int($value)) {
        return $value === 0;
    }

    if ($value instanceof Number) {
        return $value->compare(0) === 0;
    }

    return false;
}
