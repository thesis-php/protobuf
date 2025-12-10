<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum MethodIdempotencyLevel: int
{
    case Unknown = 0;
    case NoSideEffects = 1;
    case Idempotent = 2;
}
