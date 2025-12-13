<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;

/**
 * @api
 */
enum Feature: int
{
    case FEATURE_NONE = 0;
    case FEATURE_PROTO3_OPTIONAL = 1;
    case FEATURE_SUPPORTS_EDITIONS = 2;
}
