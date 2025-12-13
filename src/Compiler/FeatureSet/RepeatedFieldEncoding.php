<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum RepeatedFieldEncoding: int
{
    case REPEATED_FIELD_ENCODING_UNKNOWN = 0;
    case PACKED = 1;
    case EXPANDED = 2;
}
