<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\ExtensionRangeOptions;

/**
 * @api
 */
enum VerificationState: int
{
    case DECLARATION = 0;
    case UNVERIFIED = 1;
}
