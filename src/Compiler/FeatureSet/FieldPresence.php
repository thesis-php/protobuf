<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum FieldPresence: int
{
    case FIELD_PRESENCE_UNKNOWN = 0;
    case EXPLICIT = 1;
    case IMPLICIT = 2;
    case LEGACY_REQUIRED = 3;
}
