<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum EnumType: int
{
    case ENUM_TYPE_UNKNOWN = 0;
    case OPEN = 1;
    case CLOSED = 2;
}
