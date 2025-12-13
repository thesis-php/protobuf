<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\MethodOptions;

/**
 * @api
 */
enum IdempotencyLevel: int
{
    case IDEMPOTENCY_UNKNOWN = 0;
    case NO_SIDE_EFFECTS = 1;
    case IDEMPOTENT = 2;
}
