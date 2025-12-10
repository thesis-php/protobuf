<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum FieldPresence: int
{
    case Unknown = 0;
    case Explicit = 1;
    case Implicit = 2;
    case LegacyRequired = 3;
}
