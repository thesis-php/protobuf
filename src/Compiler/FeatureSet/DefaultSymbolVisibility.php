<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum DefaultSymbolVisibility: int
{
    case Unknown = 0;
    case ExportAll = 1;
    case ExportTopLevel = 2;
    case LocalAll = 3;
    case Strict = 4;
}
