<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldDescriptorProto;

/**
 * @api
 */
enum Label: int
{
    case LABEL_OPTIONAL = 1;
    case LABEL_REPEATED = 3;
    case LABEL_REQUIRED = 2;
}
