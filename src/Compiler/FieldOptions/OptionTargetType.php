<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldOptions;

/**
 * @api
 */
enum OptionTargetType: int
{
    case TARGET_TYPE_UNKNOWN = 0;
    case TARGET_TYPE_FILE = 1;
    case TARGET_TYPE_EXTENSION_RANGE = 2;
    case TARGET_TYPE_MESSAGE = 3;
    case TARGET_TYPE_FIELD = 4;
    case TARGET_TYPE_ONEOF = 5;
    case TARGET_TYPE_ENUM = 6;
    case TARGET_TYPE_ENUM_ENTRY = 7;
    case TARGET_TYPE_SERVICE = 8;
    case TARGET_TYPE_METHOD = 9;
}
