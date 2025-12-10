<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum Utf8Validation: int
{
    case Unknown = 0;
    case Verify = 2;
    case None = 3;
}
