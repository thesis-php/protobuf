<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

/**
 * @api
 */
enum FieldCardinality: int
{
    case Unknown = 0;
    case Optional = 1;
    case Required = 2;
    case Repeated = 3;
}
