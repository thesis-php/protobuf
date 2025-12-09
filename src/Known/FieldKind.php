<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

/**
 * @api
 */
enum FieldKind: int
{
    case Unknown = 0;
    case Double = 1;
    case Float = 2;
    case Int64 = 3;
    case Uint64 = 4;
    case Int32 = 5;
    case Fixed64 = 6;
    case Fixed32 = 7;
    case Bool = 8;
    case String = 9;
    case Group = 10;
    case Message = 11;
    case Bytes = 12;
    case Uint32 = 13;
    case Enum = 14;
    case Sfixed32 = 15;
    case Sfixed64 = 16;
    case Sint32 = 17;
    case Sint64 = 18;
}
