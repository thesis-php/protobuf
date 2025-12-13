<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum JsonFormat: int
{
    case JSON_FORMAT_UNKNOWN = 0;
    case ALLOW = 1;
    case LEGACY_BEST_EFFORT = 2;
}
