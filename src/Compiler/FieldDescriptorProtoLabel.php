<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum FieldDescriptorProtoLabel: int
{
    case Optional = 1;
    case Repeated = 3;
    case Required = 2;
}
