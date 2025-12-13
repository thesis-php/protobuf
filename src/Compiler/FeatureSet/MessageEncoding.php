<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum MessageEncoding: int
{
    case MESSAGE_ENCODING_UNKNOWN = 0;
    case LENGTH_PREFIXED = 1;
    case DELIMITED = 2;
}
