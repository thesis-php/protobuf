<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum CodeGeneratorFeature: int
{
    case None = 0;
    case Proto3Optional = 1;
    case SupportEditions = 2;
}
