<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FileOptions;

/**
 * @api
 */
enum OptimizeMode: int
{
    case SPEED = 1;
    case CODE_SIZE = 2;
    case LITE_RUNTIME = 3;
}
