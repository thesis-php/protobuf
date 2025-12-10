<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum SymbolVisibility: int
{
    case Unset = 0;
    case Local = 1;
    case Export = 2;
}
