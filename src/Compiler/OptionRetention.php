<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum OptionRetention: int
{
    case Unknown = 0;
    case Runtime = 1;
    case Source = 2;
}
