<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

/**
 * @api
 * @phpstan-sealed (
 *     NullValueKind |
 *     NumberValueKind |
 *     StringValueKind |
 *     BoolValueKind |
 *     StructValueKind |
 *     ListValueKind
 * )
 */
interface ValueKind {}
