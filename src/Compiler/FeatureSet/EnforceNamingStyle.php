<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum EnforceNamingStyle: int
{
    case ENFORCE_NAMING_STYLE_UNKNOWN = 0;
    case STYLE2024 = 1;
    case STYLE_LEGACY = 2;
}
