<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum AnnotationSemantic: int
{
    case None = 0;
    case Set = 1;
    case Alias = 2;
}
