<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum Edition: int
{
    case EditionUnknown = 0;
    case EditionLegacy = 900;
    case EditionProto2 = 998;
    case EditionProto3 = 999;
    case Edition2023 = 1000;
    case Edition2024 = 1001;
    case EditionUnstable = 9999;
    case Edition1TestOnly = 1;
    case Edition2TestOnly = 2;
    case Edition99997TestOnly = 99997;
    case Edition99998TestOnly = 99998;
    case Edition99999TestOnly = 99999;
    case EditionMax = 0x7FFFFFFF;
}
