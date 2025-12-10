<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum OptimizeMode: int
{
    case Speed = 1;
    // etc.
    case CodeSize = 2;
    case LiteRuntime = 3;
}
