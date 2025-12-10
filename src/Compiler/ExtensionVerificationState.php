<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum ExtensionVerificationState: int
{
    case Declaration = 0;
    case Unverified = 1;
}
