<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
enum Type: string
{
    case bool = 'bool';
    case int32 = 'int32';
    case sint32 = 'sint32';
    case uint32 = 'uint32';
    case int64 = 'int64';
    case sint64 = 'sint64';
    case uint64 = 'uint64';
    case sfixed32 = 'sfixed32';
    case fixed32 = 'fixed32';
    case sfixed64 = 'sfixed64';
    case fixed64 = 'fixed64';
    case float = 'float';
    case double = 'double';
    case string = 'string';
}
