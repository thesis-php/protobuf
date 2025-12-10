<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum JSType: int
{
    case Normal = 0;
    case String = 1;
    case Number = 2;
}
