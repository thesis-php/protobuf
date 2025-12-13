<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet\VisibilityFeature;

/**
 * @api
 */
enum DefaultSymbolVisibility: int
{
    case DEFAULT_SYMBOL_VISIBILITY_UNKNOWN = 0;
    case EXPORT_ALL = 1;
    case EXPORT_TOP_LEVEL = 2;
    case LOCAL_ALL = 3;
    case STRICT = 4;
}
