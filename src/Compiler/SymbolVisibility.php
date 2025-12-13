<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum SymbolVisibility: int
{
    case VISIBILITY_UNSET = 0;
    case VISIBILITY_LOCAL = 1;
    case VISIBILITY_EXPORT = 2;
}
