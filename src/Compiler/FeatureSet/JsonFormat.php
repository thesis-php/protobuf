<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum JsonFormat: int
{
    case Unknown = 0;
    case Allow = 1;
    case LegacyBestEffort = 2;
}
