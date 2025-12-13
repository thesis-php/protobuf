<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldOptions;

/**
 * @api
 */
enum JSType: int
{
    case JS_NORMAL = 0;
    case JS_STRING = 1;
    case JS_NUMBER = 2;
}
