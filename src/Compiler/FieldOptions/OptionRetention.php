<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldOptions;

/**
 * @api
 */
enum OptionRetention: int
{
    case RETENTION_UNKNOWN = 0;
    case RETENTION_RUNTIME = 1;
    case RETENTION_SOURCE = 2;
}
