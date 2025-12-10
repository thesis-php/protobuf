<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum EnforceNamingStyle: int
{
    case Unknown = 0;
    case Style2024 = 1;
    case StyleLegacy = 2;
}
