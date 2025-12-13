<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

/**
 * @api
 */
enum Edition: int
{
    case EDITION_UNKNOWN = 0;
    case EDITION_LEGACY = 900;
    case EDITION_PROTO2 = 998;
    case EDITION_PROTO3 = 999;
    case EDITION_2023 = 1000;
    case EDITION_2024 = 1001;
    case EDITION_UNSTABLE = 9999;
    case EDITION_1_TEST_ONLY = 1;
    case EDITION_2_TEST_ONLY = 2;
    case EDITION_99997_TEST_ONLY = 99997;
    case EDITION_99998_TEST_ONLY = 99998;
    case EDITION_99999_TEST_ONLY = 99999;
    case EDITION_MAX = 0x7FFFFFFF;
}
