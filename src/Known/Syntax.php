<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

/**
 * @api
 */
enum Syntax: int
{
    case PROTO2 = 0;
    case PROTO3 = 1;
    case EDITIONS = 2;
}
