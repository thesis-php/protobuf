<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum MessageEncoding: int
{
    case Unknown = 0;
    case LengthPrefixed = 1;
    case Delimited = 2;
}
