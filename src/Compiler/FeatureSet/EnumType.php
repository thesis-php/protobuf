<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FeatureSet;

/**
 * @api
 */
enum EnumType: int
{
    case Unknown = 0;
    case Open = 1;
    case Closed = 2;
}
