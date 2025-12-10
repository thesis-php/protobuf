<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum RepeatedFieldEncoding: int
{
    case Unknown = 0;
    case Packed = 1;
    case Expanded = 2;
}
