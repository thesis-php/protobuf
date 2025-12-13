<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum Utf8Validation: int
{
    case UTF8_VALIDATION_UNKNOWN = 0;
    case VERIFY = 2;
    case NONE = 3;
}
