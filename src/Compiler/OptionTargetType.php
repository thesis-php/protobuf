<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum OptionTargetType: int
{
    case Unknown = 0;
    case File = 1;
    case ExtensionRange = 2;
    case Message = 3;
    case Field = 4;
    case Oneof = 5;
    case Enum = 6;
    case EnumEntry = 7;
    case Service = 8;
    case Method = 9;
}
